<?php
/**
 * Copyright © Scalexpert.
 * This file is part of Scalexpert plugin for PrestaShop.
 *
 * @author    Société Générale
 * @copyright Scalexpert
 */

namespace ScalexpertPlugin\Api;

use Db;
use DbQuery;
use ScalexpertPlugin\Helper\BuyerFormatter;
use ScalexpertPlugin\Helper\InsuranceFormatter;
use ScalexpertPlugin\Model\CartInsurance;

class Insurance extends Entity
{
    public static $scope = 'insurance';
    public static $buyerBillingCountry = ['FR', 'DE'];

    public static function getEligibleSolutionsFromBuyerBillingCountry($buyerBillingCountry)
    {
        $params = [
            [
                'buyerBillingCountry' => $buyerBillingCountry
            ]
        ];

        $eligibleSolutions = [];

        foreach ($params as $param) {
            $apiUrl = static::$scope.'/api/v1/eligible-solutions?'.http_build_query($param);
            $result = Client::get(static::$scope, $apiUrl);

            if (!$result['hasError']) {
                foreach ($result['data']['solutions'] as $solution) {
                    if (empty($eligibleSolutions[$solution['solutionCode']])) {
                        $eligibleSolutions[$solution['solutionCode']] = $solution;
                    }
                }
            }
        }

        return $eligibleSolutions;
    }

    public static function getAllInsuranceSolutions()
    {
        $eligibleSolutions = [];

        foreach (static::$buyerBillingCountry as $country) {
            $param = [
                'buyerBillingCountry' => $country
            ];

            $apiUrl = static::$scope.'/api/v1/eligible-solutions?'.http_build_query($param);
            $result = Client::get(static::$scope, $apiUrl);

            if (!$result['hasError']) {
                foreach ($result['data']['solutions'] as $solution) {
                    if (empty($eligibleSolutions[$solution['solutionCode']])) {
                        $eligibleSolutions[$solution['solutionCode']] = $solution;
                    }
                }
            }
        }

        return $eligibleSolutions;
    }

    public static function createItem($solutionCode, $idProduct)
    {
        $product = new \Product(
            (int) $idProduct,
            false,
            \Context::getContext()->language->id,
            \Context::getContext()->shop->id
        );
        if (!\Validate::isLoadedObject($product)) {
            return [];
        }

        $data = InsuranceFormatter::normalizeInsurance($product, $solutionCode);
        /*$data = [
            "solutionCode" => "CIFRWE-DXCO",
            "sku" => "SMS4ETI14F",
            "merchantItemId" => "PS_1002",
            "brand" => "Bosch",
            "model" => "SMS4ETI14F",
            "title" => "BOSCH - Lave vaisselle 60 cm SMS4ETI14E",
            "description" => "L'EfficientDry c'est la performance de séchage améliorée grâce à l'ouverture de porte automatique en fin de cycle.Doté d'un moteur Eco Silence Drive, ce modèle de lave-vaisselle BOSCH SMS4ETI14E permet d'être extrêmement silencieux avec ses 42 dB. Nettoyez toute votre vaisselle de manière optimale avec le lave-vaisselle SMS4ETI14E de BOSCH, d'une capacité de 12 couverts et doté de 6 programmes de lavage. Profitez de la fonction de départ différé pour faire démarrer automatiquement votre lave-vaisselle au meilleur moment et réaliser ainsi des économies sur votre facture EDF. L’affichage du temps restant proposé sur ce lave-vaisselle Bosch vous permet de connaître à tout moment l’heure exacte de fin du cycle de lavage, ce qui vous permet d’organiser votre journée simplement.Ce lave-vaisselle est muni de la technologie AquaSensor qui détecte le degré de salissure, et la capacité variable automatique reconnait la charge de vaisselle afin de laver votre vaisselle afin selon ses besoins.Pour plus de confort, le panier supérieur est réglable sur 3 niveaux (RackMatic) et il est équipé de paniers VarioFlex. Pour votre sécurité, il est équipé de l'Aquastop 100% anti-fuite et d'une sécurité enfants par le verrouillage de la porte.",
            "characteristics" => "Programme Silence 42 dB. Moteur EcoSilence Drive. Echangeur Thermique. EfficientDry. VarioSpeed Plus. Départ différé et Affichage du temps restant. AquaSensor. Butée (stopper) qui empêche le déraillement du panier inférieur.",
            "category" => "lave-vaisselle"
        ];*/
        $apiUrl = static::$scope.'/api/v1/items';
        $result = Client::post(static::$scope, $apiUrl, $data);

        if ($result['hasError']) {
            return [];
        }

        return $result['data'];
    }

    public static function searchInsurances($solutionCode, $itemId, $priceItem)
    {
        $data = [
            'solutionCode' => $solutionCode,
            'itemId' => $itemId,
            'price' => $priceItem
        ];

        $apiUrl = static::$scope.'/api/v1/items/_search-insurances';
        $result = Client::post(static::$scope, $apiUrl, $data);

        if (
            $result['hasError']
            || null === $result['data']
        ) {
            return [];
        }

        if (!empty($result['data']['insurances'])) {
            $context = \Context::getContext();

            foreach ($result['data']['insurances'] as &$insurance) {
                if (isset($insurance['price'])) {
                    $insurance['formattedPrice'] = \Tools::displayPrice($insurance['price']);
                }

                if (\Validate::isLoadedObject($context->cart)) {
                    $selectedInsuranceItem = CartInsurance::getInsuranceLineByInsuranceId(
                        (int) $context->cart->id,
                        $itemId,
                        $insurance['id']
                    );

                    if (!empty($selectedInsuranceItem)) {
                        $insurance['selected'] = true;
                    }
                }

                if (empty($insurance['selected'])) {
                    $insurance['selected'] = false;
                }

                $insurance['itemId'] = $itemId;
            }
        }

        return $result['data'];
    }

    public static function createInsuranceQuotation(
        $solutionCode,
        $itemId,
        $itemPrice,
        $insuranceId
    ) {
        $data = [
            'solutionCode' => $solutionCode,
            'itemId' => $itemId,
            'itemPrice' => $itemPrice,
            'insuranceId' => $insuranceId,
        ];

        $apiUrl = static::$scope.'/api/v1/quotations';
        $result = Client::post(static::$scope, $apiUrl, $data);

        if ($result['hasError']) {
            return [];
        }

        return $result['data'];
    }

    public static function getInsuranceSubscriptionBySubscriptionId($subscriptionId)
    {
        $apiUrl = static::$scope.'/api/v1/subscriptions/' . $subscriptionId;
        $result = Client::get(static::$scope, $apiUrl);

        if ($result['hasError']) {
            return [];
        }

        return $result['data'];
    }

    public static function createInsuranceSubscription(
        $quoteData,
        $cartInsurancesProduct,
        $cart,
        $order,
        $customer
    ): array
    {
        /* @var \Cart $cart */
        if (!\Validate::isLoadedObject($cart)) {
            \Tools::redirect('index.php?controller=order&step=1');
        }

        /* @var \Order $order */
        if (!\Validate::isLoadedObject($order)) {
            \Tools::redirect('index.php?controller=order&step=1');
        }

        /* @var \Customer $customer */
        if (!\Validate::isLoadedObject($customer)) {
            \Tools::redirect('index.php?controller=order&step=1');
        }

        $currencyIsoCode = \Db::getInstance(_PS_USE_SQL_SLAVE_)
            ->getValue('SELECT `iso_code` FROM ' . _DB_PREFIX_ . 'currency WHERE `id_currency` = ' . (int) $order->id_currency);

        // Prepare variables
        $idLang = \Context::getContext()->language->id;
        $billingAddress = new \Address((int)$order->id_address_invoice, $idLang);
        $product = new \Product($cartInsurancesProduct['id_product'], false, $idLang);
        $defaultCategory = new \Category($product->id_category_default, $idLang);
        $manufacturer = new \Manufacturer($product->id_manufacturer, $idLang);
        $orderCurrency = $currencyIsoCode;

        $query = (new DbQuery())->select('od.unit_price_tax_incl')
            ->from('order_detail', 'od')
            ->where('od.id_order = ' . (int)$order->id)
            ->where('od.product_id = ' . $cartInsurancesProduct['id_product'])
            ->where('od.product_attribute_id = ' . ($cartInsurancesProduct['id_product_attribute'] ?: 0))
        ;
        $productPrice = (float)Db::getInstance()->getValue($query);
        /*$productPrice = \Product::getPriceFromOrder(
            $order->id,
            $cartInsurancesProduct['id_product'],
            $cartInsurancesProduct['id_product_attribute'] ?: 0,
            true,
            true,
            true
        );*/
        $productPrice = \Tools::ps_round($productPrice, 2);

        $params = [
            'solutionCode' => $cartInsurancesProduct['solution_code'],
            'quoteId' => $quoteData['quoteId'],
            'insuranceId' => $cartInsurancesProduct['id_insurance'],
            'merchantBasketId' => (string) $cart->id,
            'merchantBuyerId' => (string) $customer->id,
            'producerQuoteExpirationDate' => preg_replace('/^(\d{4}-\d{2}-\d{2}).*$/', '$1', $quoteData['expirationDate']),
            'producerQuoteInsurancePrice' => $quoteData['insurancePrice'],
            'buyer' => [
                'contact' => BuyerFormatter::normalizeInsuranceContact($billingAddress, $customer),
                'address' => BuyerFormatter::normalizeInsuranceAddress($billingAddress),
            ],
            'insuredItem' => [
                'id' => $cartInsurancesProduct['id_item'],
                'label' => $product->name ?? '',
                'brandName' => $manufacturer->name ?? '',
                'price' => $productPrice,
                'currencyCode' => $orderCurrency ?: '',
                'orderId' => (string) $order->reference,
                'category' => $defaultCategory->name ?? '',
                'sku' => $product->reference ?: '',
                'insurancePrice' => $quoteData['insurancePrice'],
            ]
        ];

        $apiUrl = static::$scope . '/api/v1/subscriptions';
        $result = Client::post(static::$scope, $apiUrl, $params);

        if ($result['hasError']) {
            return [];
        }

        return $result['data'];
    }
}
