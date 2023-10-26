<?php
/**
 * Copyright © Scalexpert.
 * This file is part of Scalexpert plugin for PrestaShop.
 *
 * @author    Société Générale
 * @copyright Scalexpert
 */

namespace ScalexpertPlugin\Helper\API;

use GuzzleHttp\Exception\GuzzleException;
use Monolog\Logger;
use PrestaShop\PrestaShop\Core\ConfigurationInterface;
use ScalexpertPlugin\Entity\ScalexpertCartInsurance;
use ScalexpertPlugin\Entity\ScalexpertProductCustomField;
use ScalexpertPlugin\Form\Configuration\KeysConfigurationFormDataConfiguration;
use ScalexpertPlugin\Formatter\BuyerFormatter;
use ScalexpertPlugin\Handler\HashHandler;
use ScalexpertPlugin\Handler\LogsHandler;
use Tools;

class Client
{
    private $configuration;

    private $guzzleClient;

    private $logsHandler;

    private $doctrineEntityManager;

    private $hashHandler;

    const scope_financing = 'e-financing:rw';
    const scope_insurance = 'insurance:rw';

    private $_appIdentifier;
    private $_appKey;

    private $_type;

    private $_appBearer;

    public function __construct(ConfigurationInterface $configuration, $guzzleClient, LogsHandler $logsHandler, $doctrineEntityManager, HashHandler $hashHandler)
    {
        $this->configuration = $configuration;
        $this->guzzleClient = $guzzleClient;
        $this->logsHandler = $logsHandler;
        $this->doctrineEntityManager = $doctrineEntityManager;
        $this->hashHandler = $hashHandler;

        $this->_type = $this->configuration->get(KeysConfigurationFormDataConfiguration::SCALEXPERT_KEYS_TYPE);
        if ('production' == $this->_type) {
            $this->_appIdentifier = $this->configuration->get(KeysConfigurationFormDataConfiguration::SCALEXPERT_KEYS_ID_PROD);
            $this->_appKey = $this->hashHandler->decrypt($this->configuration->get(KeysConfigurationFormDataConfiguration::SCALEXPERT_KEYS_SECRET_PROD));
        } else {
            $this->_appIdentifier = $this->configuration->get(KeysConfigurationFormDataConfiguration::SCALEXPERT_KEYS_ID_TEST);
            $this->_appKey = $this->hashHandler->decrypt($this->configuration->get(KeysConfigurationFormDataConfiguration::SCALEXPERT_KEYS_SECRET_TEST));
        }

        $this->getBearer(sprintf('%s %s', self::scope_financing, self::scope_insurance));
    }

    /**
     * @return string|null
     */
    public function getAppBearer(): ?string
    {
        return $this->_appBearer;
    }

    /**
     * @param string|null $appBearer
     */
    public function setAppBearer(?string $appBearer): void
    {
        $this->_appBearer = $appBearer;
    }

    public function setCredentials($appIdentifier, $appKey, $type): void
    {
        $this->_appIdentifier = $appIdentifier;
        $this->_appKey = $appKey;
        $this->_type = $type;
    }

    private function getBaseUrl()
    {
        if ('production' == $this->_type) {
            return 'https://api.scalexpert.societegenerale.com/baas/prod';
        }

        return 'https://api.scalexpert.uatc.societegenerale.com/baas/uatc';
    }

    public function debug(): void
    {
        dump($this->_appIdentifier, $this->_appKey, $this->_appBearer);
    }

    public function sendRequest($method, $endpoint, $formParams = [], $query = [], $headers = [], $json = [], $isBearerToken = false)
    {
        if (!$isBearerToken && empty($this->_appBearer)) {
            $this->getBearer(sprintf('%s %s', self::scope_financing, self::scope_insurance));
        }

        if (!empty($formParams)) {
            $options['form_params'] = $formParams;
        }

        if (!empty($query)) {
            $options['query'] = $query;
        }

        if (!empty($headers)) {
            $options['headers'] = $headers;
        } else {
            $options['headers'] = ['Authorization' => 'Bearer ' . $this->getAppBearer()];
        }

        if (!empty($json)) {
            $options['json'] = $json;
        }

        $response = [];
        $uniqueId = uniqid();

        try {
            $temporaryOptions = $options;
            unset($temporaryOptions['headers']);

            $this->logsHandler->addLog(
                sprintf('%s Request %s %s (environment=%s)', $uniqueId, $method, $endpoint, $this->_type),
                $temporaryOptions,
                Logger::INFO
            );

            $guzzleResponse = $this->guzzleClient->request(
                $method,
                $this->getBaseUrl() . $endpoint,
                $options
            );

            $responseBody = $guzzleResponse->getBody();
            $responseContents = $responseBody->getContents();

            $response = [
                'code' => $guzzleResponse->getStatusCode(),
                'content' => $responseContents,
                'contentsDecoded' => []
            ];

            $this->logsHandler->addLog(
                sprintf('%s Response %s (environment=%s)', $uniqueId, $endpoint, $this->_type),
                !$isBearerToken ? $response : [],
                Logger::INFO
            );

            if (!empty($responseContents)) {
                $responseContentsDecoded = json_decode($responseContents, true);
                $response['contentsDecoded'] = $responseContentsDecoded;
            }
        } catch (GuzzleException $guzzleException) {
            $this->logsHandler->addLog(
                sprintf('%s Error %s (environment=%s)', $uniqueId, $endpoint, $this->_type),
                [
                    'errorCode' => $guzzleException->getCode(),
                    'errorMessage' => $guzzleException->getMessage(),
                ],
                Logger::ERROR
            );
        }

        return $response;
    }

    public function getBearer($scope = null): bool
    {
        $response = $this->sendRequest(
            'POST',
            '/auth-server/api/v1/oauth2/token',
            [
                'grant_type' => 'client_credentials',
                'scope' => $scope ?? '',
            ],
            [],
            ['Authorization' => 'Basic ' . base64_encode($this->_appIdentifier . ':' . $this->_appKey)],
            [],
            true
        );

        if (!empty($response['contentsDecoded']['access_token'])) {
            $this->_appBearer = $response['contentsDecoded']['access_token'];
            return true;
        }

        return false;
    }

    public function getFinancialSolutions($price, $buyerBillingCountry): array
    {
        $response = $this->sendRequest(
            'GET',
            '/e-financing/api/v1/eligible-solutions',
            [],
            [
                'financedAmount' => $price,
                'buyerBillingCountry' => $buyerBillingCountry,
            ]
        );

        $financialSolutions = [];

        if (!empty($response['contentsDecoded']['solutions'])) {
            foreach ($response['contentsDecoded']['solutions'] as $solution) {
                $financialSolutions[$solution['solutionCode']] = $this->formatSolution(
                    $solution,
                    $buyerBillingCountry,
                    'financial'
                );
            }
        }

        return $financialSolutions;
    }

    public function getAllFinancialSolutions(): array
    {
        $financialSolutionsToRequest = [
            [
                'financedAmount' => '500',
                'buyerBillingCountry' => 'FR',
            ],
            [
                'financedAmount' => '1000',
                'buyerBillingCountry' => 'FR',
            ],
            [
                'financedAmount' => '500',
                'buyerBillingCountry' => 'DE',
            ],
            [
                'financedAmount' => '1000',
                'buyerBillingCountry' => 'DE',
            ],
        ];

        $financialSolutions = [];

        foreach ($financialSolutionsToRequest as $financialSolution) {
            $response = $this->sendRequest(
                'GET',
                '/e-financing/api/v1/eligible-solutions',
                [],
                [
                    'financedAmount' => $financialSolution['financedAmount'],
                    'buyerBillingCountry' => $financialSolution['buyerBillingCountry'],
                ]
            );

            if (!empty($response['contentsDecoded']['solutions'])) {
                foreach ($response['contentsDecoded']['solutions'] as $solution) {
                    $financialSolutions[$solution['solutionCode']] = $this->formatSolution(
                        $solution,
                        $financialSolution['buyerBillingCountry'],
                        'financial'
                    );
                }
            }
        }

        return $financialSolutions;
    }

    public function getInsuranceSolutionsByBuyerBillingCountry($buyerBillingCountry): array
    {
        $response = $this->sendRequest(
            'GET',
            '/insurance/api/v1/eligible-solutions',
            [],
            [
                'buyerBillingCountry' => $buyerBillingCountry,
            ]
        );

        $insuranceSolutions = [];

        if (!empty($response['contentsDecoded']['solutions'])) {
            foreach ($response['contentsDecoded']['solutions'] as $solution) {
                $insuranceSolutions[$solution['solutionCode']] = $this->formatSolution(
                    $solution,
                    $buyerBillingCountry,
                    'insurance'
                );
            }
        }

        return $insuranceSolutions;
    }

    public function getAllInsuranceSolutions(): array
    {
        $insuranceSolutionsToRequest = [
            [
                'buyerBillingCountry' => 'FR',
            ],
            [
                'buyerBillingCountry' => 'DE',
            ],
        ];

        $insuranceSolutions = [];

        foreach ($insuranceSolutionsToRequest as $insuranceSolution) {
            $response = $this->sendRequest(
                'GET',
                '/insurance/api/v1/eligible-solutions',
                [],
                [
                    'buyerBillingCountry' => $insuranceSolution['buyerBillingCountry'],
                ]
            );

            if (!empty($response['contentsDecoded']['solutions'])) {
                foreach ($response['contentsDecoded']['solutions'] as $solution) {
                    $insuranceSolutions[$solution['solutionCode']] = $this->formatSolution(
                        $solution,
                        $insuranceSolution['buyerBillingCountry'],
                        'insurance'
                    );
                }
            }
        }

        return $insuranceSolutions;
    }

    public function createItem($solutionCode, $productID): string
    {
        $itemId = '';
        $defaultLang = \Configuration::get('PS_LANG_DEFAULT');
        $product = new \Product((int) $productID, false, $defaultLang);

        // Get category name
        if (!empty($product->id_category_default)) {
            $defaultCategory = new \Category($product->id_category_default);

            if (\Validate::isLoadedObject($defaultCategory)) {
                $categoryName = $defaultCategory->getName($defaultLang);
            }
        }

        $repository = $this->doctrineEntityManager->getRepository(ScalexpertProductCustomField::class);

        if (!empty($repository)) {
            $productCustomFields = $repository->find($productID);

            if (!empty($productCustomFields)) {
                $model = $productCustomFields->getModel();
                $characteristics = $productCustomFields->getCharacteristics();
            }
        }

        if (!empty($product->id_manufacturer)) {
            $manufacturerName = \Manufacturer::getNameById($product->id_manufacturer);
        }

        $response = $this->sendRequest(
            'POST',
            '/insurance/api/v1/items',
            [],
            [],
            [],
            [
                'solutionCode' => $solutionCode,
                'sku' => $product->reference,
                'merchantItemId' => (string) $product->id,
                'brand' => $manufacturerName ?? '',
                'model' => $model ?? '',
                'title' => $product->name ?? '',
                'description' => !empty($product->description) ? substr(strip_tags($product->description), 0, 255) : '',
                'characteristics' => $characteristics ?? '',
                'category' => $categoryName ?? '',
            ]
        );

        if (!empty($response['contentsDecoded']['id'])) {
            $itemId = $response['contentsDecoded']['id'];
        }

        return $itemId;
    }

    public function getInsurancesByItemId($solutionCode, $productPrice, $itemId): array
    {
        $response = $this->sendRequest(
            'POST',
            '/insurance/api/v1/items/_search-insurances',
            [],
            [],
            [],
            [
                'solutionCode' => $solutionCode,
                'itemId' => $itemId,
                'price' => $productPrice,
            ]
        );

        $insurances = [];

        if (!empty($response['contentsDecoded']['insurances'])) {
            $context = \Context::getContext();

            foreach ($response['contentsDecoded']['insurances'] as $insurance) {
                $insuranceData = $insurance;

                if (isset($insuranceData['price'])) {
                    $insuranceData['formattedPrice'] = \Tools::displayPrice($insuranceData['price']);
                }

                if (!empty($context) && !empty($context->cart)) {
                    $cartInsuranceRepository = $this->doctrineEntityManager->getRepository(ScalexpertCartInsurance::class);

                    $selectedInsuranceItem = $cartInsuranceRepository->findBy([
                        'idCart' => (int) $context->cart->id,
                        'idItem' => $itemId,
                        'idInsurance' => $insuranceData['id'],
                    ]);

                    if (!empty($selectedInsuranceItem)) {
                        $insuranceData['selected'] = true;
                    }
                }

                if (empty($insuranceData['selected'])) {
                    $insuranceData['selected'] = false;
                }

                $insuranceData['itemId'] = $itemId;
                $insurances[] = $insuranceData;
            }
        }

        return $insurances;
    }

    public function createInsuranceQuotation($solutionCode, $itemId, $itemPrice, $insuranceId): array
    {
        $response = $this->sendRequest(
            'POST',
            '/insurance/api/v1/quotations',
            [],
            [],
            [],
            [
                'solutionCode' => $solutionCode,
                'itemId' => $itemId,
                'itemPrice' => $itemPrice,
                'insuranceId' => $insuranceId,
            ]
        );

        $quoteData = [];

        if (!empty($response['contentsDecoded']['quoteId'])) {
            $quoteData['quoteId'] = $response['contentsDecoded']['quoteId'];
        }

        if (!empty($response['contentsDecoded']['insurancePrice'])) {
            $quoteData['insurancePrice'] = $response['contentsDecoded']['insurancePrice'];
        }

        if (!empty($response['contentsDecoded']['expirationDate'])) {
            $quoteData['expirationDate'] = $response['contentsDecoded']['expirationDate'];
        }

        return $quoteData;
    }

    public function getFinancingSubscriptionsByOrderReference($merchantGlobalOrderId): array
    {
        $response = $this->sendRequest(
            'GET',
            '/e-financing/api/v1/subscriptions',
            [],
            [
                'merchantGlobalOrderId' => $merchantGlobalOrderId,
            ]
        );

        $financingSubscriptionsData = [];

        if (!empty($response['contentsDecoded']['subscriptions'])) {
            foreach ($response['contentsDecoded']['subscriptions'] as $financialSubscription) {
                $financingSubscriptionsData[] = [
                    'creditSubscriptionId' => $financialSubscription['creditSubscriptionId'] ?? '',
                    'registrationTimestamp' => $financialSubscription['registrationTimestamp'] ?? '',
                    'lastUpdateTimestamp' => $financialSubscription['lastUpdateTimestamp'] ?? '',
                    'solutionCode' => $financialSubscription['solutionCode'] ?? '',
                    'marketTypeCode' => $financialSubscription['marketTypeCode'] ?? '',
                    'implementationCode' => $financialSubscription['implementationCode'] ?? '',
                    'merchantBasketId' => $financialSubscription['merchantBasketId'] ?? '',
                    'merchantGlobalOrderId' => $financialSubscription['merchantGlobalOrderId'] ?? '',
                    'buyerFinancedAmount' => $financialSubscription['buyerFinancedAmount'] ?? '',
                    'consolidatedStatus' => $financialSubscription['consolidatedStatus'] ?? '',
                ];
            }
        }

        return $financingSubscriptionsData;
    }

    public function getAllFinancingSubscriptions(): array
    {
        $response = $this->sendRequest(
            'GET',
            '/e-financing/api/v1/subscriptions?pageSize=500000'
        );

        if (!empty($response['contentsDecoded']['subscriptions'])) {
           return $response['contentsDecoded']['subscriptions'];
        }

        return [];
    }

    public function getFinancingSubscriptionBySubscriptionId($subscriptionId): array
    {
        $response = $this->sendRequest(
            'GET',
            '/e-financing/api/v1/subscriptions/' . $subscriptionId
        );

        $financingSubscriptionData = [];

        if (!empty($response['contentsDecoded'])) {
            $financingSubscriptionData = $response['contentsDecoded'];
        }

        return $financingSubscriptionData;
    }

    public function getInsuranceSubscriptionsByCartId($merchantBasketId): array
    {
        $response = $this->sendRequest(
            'GET',
            '/insurance/api/v1/subscriptions',
            [],
            [
                'merchantBasketId' =>  'ps_' . $merchantBasketId,
            ]
        );

        $insuranceSubscriptionsData = [];

        if (!empty($response['contentsDecoded']['subscriptions'])) {
            foreach ($response['contentsDecoded']['subscriptions'] as $insuranceSubscription) {
                $insuranceSubscriptionsData[] = [
                    'insuranceSubscriptionId' => $insuranceSubscription['insuranceSubscriptionId'] ?? '',
                    'registrationTimestamp' => $insuranceSubscription['registrationTimestamp'] ?? '',
                    'lastUpdateTimestamp' => $insuranceSubscription['lastUpdateTimestamp'] ?? '',
                    'solutionCode' => $insuranceSubscription['solutionCode'] ?? '',
                    'marketTypeCode' => $insuranceSubscription['marketTypeCode'] ?? '',
                    'duration' => $insuranceSubscription['duration'] ?? '',
                    'merchantBasketId' => $insuranceSubscription['merchantBasketId'] ?? '',
                    'consolidatedStatus' => $insuranceSubscription['consolidatedStatus'] ?? '',
                    'insuredItemPrice' => $insuranceSubscription['insuredItemPrice'] ?? '',
                    'insuredItemCategory' => $insuranceSubscription['insuredItemCategory'] ?? '',
                    'producerQuoteInsurancePrice' => $insuranceSubscription['producerQuoteInsurancePrice'] ?? '',
                    'merchantCommission' => $insuranceSubscription['merchantCommission'] ?? '',
                ];
            }
        }

        return $insuranceSubscriptionsData;
    }

    public function getInsuranceSubscriptionBySubscriptionId($subscriptionId): array
    {
        $response = $this->sendRequest(
            'GET',
            '/insurance/api/v1/subscriptions/' . $subscriptionId
        );

        if (!empty($response['contentsDecoded'])) {
            return $response['contentsDecoded'];
        }

        return [];
    }

    public function createInsuranceSubscription($quoteData, $cartInsurancesProduct, $cart, $order, $customer): ?array
    {
        $address = new \Address((int) $order->id_address_invoice);
        $defaultLang = \Configuration::get('PS_LANG_DEFAULT');
        $product = new \Product($cartInsurancesProduct->getIdProduct(), false, $defaultLang);

        // Get category name
        if (!empty($product->id_category_default)) {
            $defaultCategory = new \Category($product->id_category_default);

            if (\Validate::isLoadedObject($defaultCategory)) {
                $categoryName = $defaultCategory->getName($defaultLang);
            }
        }

        if (!empty($product->id_manufacturer)) {
            $manufacturerName = \Manufacturer::getNameById($product->id_manufacturer);
        }

        $productPrice = \Product::getPriceFromOrder(
            $order->id,
            $cartInsurancesProduct->getIdProduct(),
            $cartInsurancesProduct->getIdProductAttribute() ?: 0,
            true,
            true,
            true
        );
        $productPrice = \Tools::ps_round($productPrice, 2);

        $orderCurrency = \Currency::getIsoCodeById((int) $order->id_currency);

        $mobilePhoneNumber = !empty($address->phone_mobile) ? $address->phone_mobile : $address->phone;

        if (strpos($mobilePhoneNumber, '+') === false) {
            $addressCountry = new \Country($address->id_country);

            if (!empty($addressCountry->call_prefix)) {
                $mobilePhoneNumber = sprintf('+%s%s', $addressCountry->call_prefix, substr($mobilePhoneNumber, 1));
            } else {
                $mobilePhoneNumber = sprintf('+33%s', substr($mobilePhoneNumber, 1));
            }
        }

        $response = $this->sendRequest(
            'POST',
            '/insurance/api/v1/subscriptions',
            [],
            [],
            [],
            [
                'solutionCode' => $cartInsurancesProduct->getSolutionCode(),
                'quoteId' => $quoteData['quoteId'],
                'insuranceId' => $cartInsurancesProduct->getIdInsurance(),
                'merchantBasketId' => 'ps_' . (string) $cart->id,
                'merchantBuyerId' => (string) $customer->id,
                'producerQuoteExpirationDate' => preg_replace('/^(\d{4}-\d{2}-\d{2}).*$/', '$1', $quoteData['expirationDate']),
                'producerQuoteInsurancePrice' => $quoteData['insurancePrice'],
                'buyer' => [
                    'contact' => [
                        'lastName' => $customer->lastname ?: '',
                        'firstName' => $customer->firstname ?: '',
                        'email' => $customer->email ?: '',
                        'mobilePhoneNumber' => $mobilePhoneNumber,
                        'phoneNumber' => '',
                    ],
                    'address' => [
                        'streetNumber' => 0,
                        'streetNumberSuffix' => '',
                        'streetName' => $address->address1 ?: '',
                        'streetNameComplement' => $address->address2 ?: '',
                        'zipCode' => $address->postcode ?: '',
                        'cityName' => $address->city ?: '',
                        'regionName' => \State::getNameById($address->id_state) ?: 'default',
                        'countryCode' => \Country::getIsoById($address->id_country) ?: '',
                    ]
                ],
                'insuredItem' => [
                    'id' => $cartInsurancesProduct->getIdItem(),
                    'label' => $product->name ?? '',
                    'brandName' => $manufacturerName ?? '',
                    'price' => $productPrice,
                    'currencyCode' => $orderCurrency ?: '',
                    'orderId' => (string) $order->reference,
                    'category' => $categoryName ?? '',
                    'sku' => $product->reference ?: '',
                    'insurancePrice' => $quoteData['insurancePrice'],
                ]
            ]
        );

        $insuranceSubscriptionData = [];

        if (!empty($response['contentsDecoded']['insuranceSubscriptionId'])) {
            $insuranceSubscriptionData['insuranceSubscriptionId'] = $response['contentsDecoded']['insuranceSubscriptionId'];
        }

        if (!empty($response['contentsDecoded']['consolidatedStatus'])) {
            $insuranceSubscriptionData['consolidatedStatus'] = $response['contentsDecoded']['consolidatedStatus'];
        }

        return $insuranceSubscriptionData;
    }

    public function createFinancingSubscription($order, $solutionCode): array
    {
        $customer = new \Customer((int) $order->id_customer);
        $shippingAddress = new \Address((int) $order->id_address_delivery);
        $billingAddress = new \Address((int) $order->id_address_invoice);
        $orderCurrency = \Currency::getIsoCodeById((int) $order->id_currency);
        $languageDefault = \Configuration::get('PS_LANG_DEFAULT');

        $orderShipping = $order->getShipping();

        if (!empty($orderShipping)) {
            if (isset(reset($orderShipping)['carrier_name'])) {
                $carrierName = reset($orderShipping)['carrier_name'];
            }
        }

        $lastOrders = \Order::getCustomerOrders($customer->id);

        if (!empty($lastOrders) && is_array($lastOrders)) {
            if (isset($lastOrders[1]['date_add'])) {
                $lastDatePurchase = preg_replace('/^(\d{4}-\d{2}-\d{2}).*$/', '$1', $lastOrders[1]['date_add']);
            }
        }

        $orderProducts = $order->getProducts();
        $basketItems = [];

        if (!empty($orderProducts)) {
            $repository = $this->doctrineEntityManager->getRepository(ScalexpertProductCustomField::class);

            foreach ($orderProducts as $orderProduct) {
                // Get category name
                if (!empty($orderProduct['id_category_default'])) {
                    $defaultCategory = new \Category($orderProduct['id_category_default']);

                    if (\Validate::isLoadedObject($defaultCategory)) {
                        $categoryName = $defaultCategory->getName($languageDefault);
                    }
                }

                if (!empty($repository)) {
                    $productCustomFields = $repository->find($orderProduct['id_product']);

                    if (!empty($productCustomFields)) {
                        $model = $productCustomFields->getModel();
                        $characteristics = $productCustomFields->getCharacteristics();
                    }
                }

                if (!empty($orderProduct['id_manufacturer'])) {
                    $manufacturerName = \Manufacturer::getNameById($orderProduct['id_manufacturer']);
                }

                $basketItems[] = [
                    'id' => (string) $orderProduct['id_product'] ?: 'NC',
                    'quantity' => (int) $orderProduct['product_quantity'] ?: 1,
                    'model' => !empty($model) ? $model : 'NC',
                    'label' => trim(strip_tags(substr($orderProduct['product_name'], 0, 30))) ?: 'NC',
                    'price' => (float) \Tools::ps_round($orderProduct['unit_price_tax_incl'], 2) ?: 0,
                    'currencyCode' => $orderCurrency ?: 'NC',
                    'orderId' => (string) $order->reference,
                    'brandName' => $manufacturerName ?? 'NC',
                    'description' => !empty($orderProduct['description']) ? substr(strip_tags($orderProduct['description']), 0, 255) : 'NC',
                    'specifications' => !empty($characteristics) ? $characteristics : 'NC',
                    'category' => !empty($categoryName) ? substr($categoryName, 0, 20) : 'NC',
                    'sku' => (string) $orderProduct['product_reference'] ?: 'NC',
                    'isFinanced' => true,
                ];
            }
        }

        $redirectURL = \Context::getContext()->link->getModuleLink(
            'scalexpertplugin',
            'confirmation',
            [
                'cart_id' => $order->id_cart,
                'order_id' => $order->id,
                'k' => $order->secure_key,
            ]
        );

        $data = [
            'solutionCode' => $solutionCode,
            'merchantBasketId' => (string) $order->id_cart,
            'merchantGlobalOrderId' => (string) $order->reference,
            'merchantBuyerId' => (string) $customer->id,
            'financedAmount' => (float) \Tools::ps_round($order->total_paid, 2),
            'merchantUrls' => [
                'confirmation' => $redirectURL,
            ],
            'buyers' => [
                [
                    'billingContact' => BuyerFormatter::normalizeContact($billingAddress, $customer),
                    'billingAddress' => BuyerFormatter::normalizeAddress($billingAddress, 'BILLING_ADDRESS'),
                    'deliveryContact' => BuyerFormatter::normalizeContact($shippingAddress, $customer),
                    'deliveryAddress' => BuyerFormatter::normalizeAddress($shippingAddress, 'DELIVERY_ADDRESS'),
                    'contact' => BuyerFormatter::normalizeContact($shippingAddress, $customer),
                    'contactAddress' => BuyerFormatter::normalizeAddress($shippingAddress, 'MAIN_ADDRESS'),
                    'birthName' => '',
                    'birthDate' => (!empty($customer->birthday) && '0000-00-00' !== $customer->birthday) ? $customer->birthday : '',
                    'birthCityName' => '',
                    // 'birthCountryName' => '',
                    'deliveryMethod' => $carrierName ?? 'NC',
                    'lastDatePurchase' => $lastDatePurchase ?? '',
                    'vip' => false,
                ],
            ],
            'basketDetails' => [
                'basketItems' => $basketItems,
            ]
        ];

        $response = $this->sendRequest(
            'POST',
            '/e-financing/api/v1/subscriptions',
            [],
            [],
            [],
            $data
        );

        $financingSubscriptionData = [];

        if (!empty($response['contentsDecoded']['id'])) {
            $financingSubscriptionData['id'] = $response['contentsDecoded']['id'];
        }

        if (!empty($response['contentsDecoded']['redirect'])) {
            $financingSubscriptionData['redirect'] = [
                'type' => $response['contentsDecoded']['redirect']['type'],
                'value' => $response['contentsDecoded']['redirect']['value']
            ];
        }

        return $financingSubscriptionData;
    }

    public function cancelFinancialSubscription($insuranceId, $amount)
    {
        $response = $this->sendRequest(
            'POST',
            '/e-financing/api/v1/subscriptions/'.$insuranceId.'/_cancel',
            [],
            [],
            [],
            [
                'cancelledAmount' => (float)Tools::ps_round($amount, 2),
            ]
        );

        $responseData = [];

        if (!empty($response['contentsDecoded']['status'])) {
            $responseData['status'] = $response['contentsDecoded']['status'];
        }

        return $responseData;
    }

    public function formatSolution($solution, $buyerBillingCountry, $solutionType): array
    {
        return [
            'solutionCode' => $solution['solutionCode'] ?? '',
            'visualTitle' =>  $solution['communicationKit']['visualTitle'] ?? '',
            'visualDescription' =>  $solution['communicationKit']['visualDescription'] ?? '',
            'visualInformationIcon' => $solution['communicationKit']['visualInformationIcon'] ?? '',
            'visualAdditionalInformation' => $solution['communicationKit']['visualAdditionalInformation'] ?? '',
            'visualLegalText' => $solution['communicationKit']['visualLegalText'] ?? '',
            'visualTableImage' => $solution['communicationKit']['visualTableImage'] ?? '',
            'visualLogo' => $solution['communicationKit']['visualLogo'] ?? '',
            'visualInformationNoticeURL' => $solution['communicationKit']['visualInformationNoticeURL'] ?? '',
            'visualProductTermsURL' => $solution['communicationKit']['visualProductTermsURL'] ?? '',
            'buyerBillingCountry' => $buyerBillingCountry,
            'countryFlag' => sprintf('/img/flags/%s.jpg', strtolower($buyerBillingCountry)),
            'type' => $solutionType,
        ];
    }
}