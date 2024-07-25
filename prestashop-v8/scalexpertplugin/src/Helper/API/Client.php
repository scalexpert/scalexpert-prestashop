<?php
/**
 * Copyright © Scalexpert.
 * This file is part of Scalexpert plugin for PrestaShop. See COPYING.md for license details.
 *
 * @author    Scalexpert (https://scalexpert.societegenerale.com/)
 * @copyright Scalexpert
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

namespace ScalexpertPlugin\Helper\API;

use GuzzleHttp\Exception\GuzzleException;
use Monolog\Logger;
use PrestaShop\PrestaShop\Core\ConfigurationInterface;
use ScalexpertPlugin\Entity\ScalexpertCartInsurance;
use ScalexpertPlugin\Entity\ScalexpertProductCustomField;
use ScalexpertPlugin\Form\Configuration\KeysConfigurationFormDataConfiguration;
use ScalexpertPlugin\Formatter\BasketFormatter;
use ScalexpertPlugin\Formatter\BuyerFormatter;
use ScalexpertPlugin\Handler\HashHandler;
use ScalexpertPlugin\Handler\LogsHandler;
use ScalexpertPlugin\Helper\SolutionFormatter;
use Symfony\Component\Dotenv\Dotenv;
use Tools;

class Client
{
    public const SCOPE_FINANCING = 'e-financing:rw';
    public const SCOPE_INSURANCE = 'insurance:rw';

    private $configuration;

    private $guzzleClient;

    private $logsHandler;

    private $doctrineEntityManager;


    private $_appIdentifier;
    private $_appKey;

    private $_type;

    private $_appBearer;
    private $solutionFormatter;

    public function __construct(
        ConfigurationInterface $configuration,
                               $guzzleClient,
        LogsHandler            $logsHandler,
                               $doctrineEntityManager,
        HashHandler            $hashHandler,
        SolutionFormatter      $solutionFormatter
    )
    {
        $this->configuration = $configuration;
        $this->guzzleClient = $guzzleClient;
        $this->logsHandler = $logsHandler;
        $this->doctrineEntityManager = $doctrineEntityManager;
        $this->solutionFormatter = $solutionFormatter;

        $this->_type = $this->configuration->get(KeysConfigurationFormDataConfiguration::SCALEXPERT_KEYS_TYPE);
        if ('production' === $this->_type) {
            $keyId = KeysConfigurationFormDataConfiguration::SCALEXPERT_KEYS_ID_PROD;
            $keySecret = KeysConfigurationFormDataConfiguration::SCALEXPERT_KEYS_SECRET_PROD;
        } else {
            $keyId = KeysConfigurationFormDataConfiguration::SCALEXPERT_KEYS_ID_TEST;
            $keySecret = KeysConfigurationFormDataConfiguration::SCALEXPERT_KEYS_SECRET_TEST;
        }
        $this->_appIdentifier = $this->configuration->get($keyId);
        $this->_appKey = $hashHandler->decrypt($this->configuration->get($keySecret));

        $this->getBearer(sprintf('%s %s', self::SCOPE_FINANCING, self::SCOPE_INSURANCE));
    }

    public function setCredentials($appIdentifier, $appKey, $type): void
    {
        $this->_appIdentifier = $appIdentifier;
        $this->_appKey = $appKey;
        $this->_type = $type;
    }

    private function getBaseUrl(): string
    {
        $dotEnv = new Dotenv();
        $dotEnv->loadEnv(__DIR__ . '/../../../.env');

        if ('production' === $this->_type) {
            return getenv('SCALEXPERT_API_PRODUCTION_URL');
        }

        return getenv('SCALEXPERT_API_TEST_URL');
    }

    public function sendRequest(
        $method,
        $endpoint,
        $formParams = [],
        $query = [],
        $headers = [],
        $json = [],
        $isBearerToken = false
    ): array
    {
        if (!$isBearerToken && empty($this->_appBearer)) {
            $this->getBearer(sprintf('%s %s', self::SCOPE_FINANCING, self::SCOPE_INSURANCE));
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
            $options['headers'] = ['Authorization' => 'Bearer ' . $this->_appBearer];
        }

        if (!empty($json)) {
            $options['json'] = $json;
        }

        $response = [];
        $uniqueId = uniqid('', true);

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

    public function getFinancialSolutions(
        $price,
        string $buyerBillingCountry
    ): array
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
                $financialSolutions[$solution['solutionCode']] = $this->solutionFormatter->formatSolution(
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
            $financialSolutions[] = $this->getFinancialSolutions(
                $financialSolution['financedAmount'],
                $financialSolution['buyerBillingCountry']
            );
        }

        return array_merge([], ...$financialSolutions);
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
                $insuranceSolutions[$solution['solutionCode']] = $this->solutionFormatter->formatSolution(
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
            $insuranceSolutions[] = $this->getInsuranceSolutionsByBuyerBillingCountry(
                $insuranceSolution['buyerBillingCountry']
            );
        }

        return array_merge([], ...$insuranceSolutions);
    }

    public function createItem($solutionCode, $productID): string
    {
        $itemId = '';
        $defaultLang = $this->configuration->get('PS_LANG_DEFAULT');
        $product = new \Product((int)$productID, false, $defaultLang);

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
                'sku' => !empty($product->ean13) ? (string)$product->ean13 : 'NC',
                'merchantItemId' => (string)$product->reference,
                'brand' => !empty($manufacturerName) ? $manufacturerName : 'NC',
                'model' => !empty($model) ? $model : 'NC',
                'title' => !empty($product->name) ? $product->name : 'NC',
                'description' => !empty($product->description) ?
                    substr(strip_tags($product->description), 0, 255) : 'NC',
                'characteristics' => !empty($characteristics) ? $characteristics : 'NC',
                'category' => !empty($categoryName) ? $categoryName : 'NC',
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

        if (empty($response['contentsDecoded']['insurances'])) {
            return [];
        }

        $insurances = [];
        $context = \Context::getContext();

        foreach ($response['contentsDecoded']['insurances'] as $insurance) {
            $insuranceData = $insurance;

            if (null !== $context && isset($insuranceData['price'])) {
                $insuranceData['formattedPrice'] = $context->getCurrentLocale()->formatPrice(
                    (float)$insuranceData['price'],
                    $context->currency->iso_code
                );
            }

            if (null !== $context && !empty($context->cart)) {
                $cartInsuranceRepository = $this->doctrineEntityManager->getRepository(ScalexpertCartInsurance::class);
                $selectedInsuranceItem = $cartInsuranceRepository->findBy([
                    'idCart' => (int)$context->cart->id,
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
                    'consolidatedSubstatus' => $financialSubscription['consolidatedSubstatus'] ?? '',
                    'isReturned' => isset($financialSubscription['isReturned']) ? (bool)$financialSubscription['isReturned'] : false,
                    'isDelivered' => isset($financialSubscription['isDelivered']) ? (bool)$financialSubscription['isDelivered'] : false,
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

        if (!empty($response['contentsDecoded'])) {
            return $response['contentsDecoded'];
        }

        return [];
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

    public function createInsuranceSubscription(
        $quoteData,
        ScalexpertCartInsurance $cartInsurancesProduct,
        $cart,
        $order,
        \Customer $customer
    ): ?array
    {
        $address = new \Address((int)$order->id_address_invoice);
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
        $orderCurrency = \Currency::getIsoCodeById((int)$order->id_currency);

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
                'merchantGlobalOrderId' => (string)$order->reference,
                'merchantBasketId' => (string)$cart->id,
                'merchantBuyerId' => (string)$customer->id,
                'producerQuoteExpirationDate' => preg_replace(
                    '/^(\d{4}-\d{2}-\d{2}).*$/',
                    '$1',
                    $quoteData['expirationDate']
                ),
                'producerQuoteInsurancePrice' => $quoteData['insurancePrice'],
                'buyer' => BuyerFormatter::normalizeBuyer($customer, $address),
                'insuredItem' => [
                    'id' => (string)$cartInsurancesProduct->getIdItem() ?: 'NC',
                    'label' => trim(strip_tags(substr($product->name, 0, 30))) ?: 'NC',
                    'brandName' => $manufacturerName ?? 'NC',
                    'price' => $productPrice,
                    'currencyCode' => $orderCurrency ?: 'NC',
                    'orderId' => (string)$order->reference,
                    'category' => !empty($categoryName) ? substr($categoryName, 0, 20) : 'NC',
                    'sku' => (string)$product->reference ?: 'NC',
                    'insurancePrice' => $quoteData['insurancePrice'],
                ]
            ]
        );

        return [
            'insuranceSubscriptionId' => $response['contentsDecoded']['insuranceSubscriptionId'] ?? '',
            'consolidatedStatus' => $response['contentsDecoded']['consolidatedStatus'] ?? '',
        ];
    }

    public function createFinancingSubscription($order, $solutionCode): array
    {
        $customer = new \Customer((int)$order->id_customer);
        $shippingAddress = new \Address((int)$order->id_address_delivery);
        $billingAddress = new \Address((int)$order->id_address_invoice);

        $orderShipping = $order->getShipping();
        if (
            !empty($orderShipping)
            && isset(reset($orderShipping)['carrier_name'])
        ) {
            $carrierName = reset($orderShipping)['carrier_name'];
        }

        $basketItems = [];
        $repository = $this->doctrineEntityManager->getRepository(ScalexpertProductCustomField::class);

        foreach ($order->getProducts() as $orderProduct) {
            $defaultCategory = new \Category($orderProduct['id_category_default'], $customer->id_lang);
            $manufacturerName = \Manufacturer::getNameById($orderProduct['id_manufacturer']);

            /* @var ScalexpertProductCustomField $productCustomFields */
            $productCustomFields = $repository->find($orderProduct['id_product']);
            $model = '';
            $characteristics = '';
            if (null !== $productCustomFields) {
                $model = $productCustomFields->getModel();
                $characteristics = $productCustomFields->getCharacteristics();
            }

            $basketItems[] = BasketFormatter::normalizeItem(
                $orderProduct,
                $model,
                $characteristics,
                $defaultCategory,
                $manufacturerName,
                $order
            );
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
            'merchantBasketId' => (string)$order->id_cart,
            'merchantGlobalOrderId' => (string)$order->reference,
            'merchantBuyerId' => (string)$customer->id,
            'financedAmount' => \Tools::ps_round($order->total_paid, 2),
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
                    'deliveryMethod' => $carrierName ?? 'NC',
                    'birthName' => !empty($customer->lastname) ? $customer->lastname : '',
                    'birthDate' => (!empty($customer->birthday) && '0000-00-00' !== $customer->birthday) ?
                        $customer->birthday : '1970-01-01',
                    'birthCityName' => '',
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

        return [
            'id' => $response['contentsDecoded']['id'] ?? '',
            'redirect' => [
                'type' => $response['contentsDecoded']['redirect']['type'] ?? '',
                'value' => $response['contentsDecoded']['redirect']['value'] ?? ''
            ],
        ];
    }

    public function cancelFinancialSubscription($insuranceId, $amount): array
    {
        $response = $this->sendRequest(
            'POST',
            '/e-financing/api/v1/subscriptions/' . $insuranceId . '/_cancel',
            [],
            [],
            [],
            [
                'cancelledAmount' => Tools::ps_round($amount, 2),
            ]
        );

        $responseData = [];

        if (!empty($response['contentsDecoded']['status'])) {
            $responseData['status'] = $response['contentsDecoded']['status'];
        }

        return $responseData;
    }

    public function confirmDeliveryFinancingSubscription(
        $creditSubscriptionId,
        $trackingNumber = '',
        $operator = '',
        $withFullPayload = true
    ): array
    {
        if ($withFullPayload) {
            $data = [
                'isDelivered' => true,
                'trackingNumber' => $trackingNumber,
                'operator' => $operator,
            ];
        } else {
            $data = [
                'isDelivered' => true,
            ];
        }

        return $this->sendRequest(
            'POST',
            '/e-financing/api/v1/subscriptions/' . $creditSubscriptionId . '/_confirmDelivery',
            [],
            [],
            [],
            $data
        );
    }

    public function simulateFinancing(
        $financedAmount,
        $buyerBillingCountry = '',
        $solutionCodes = []
    ): array
    {
        $data = [
            'financedAmount' => (float)$financedAmount,
            'buyerBillingCountry' => strtoupper($buyerBillingCountry),
            'solutionCodes' => $solutionCodes,
        ];

        $response = $this->sendRequest(
            'POST',
            '/e-financing/api/v1/_simulate-solutions',
            [],
            [],
            [],
            $data
        );

        $responseData = [];

        if (!empty($response['contentsDecoded'])) {
            $responseData = $response['contentsDecoded'];
        }

        return $responseData;
    }
}
