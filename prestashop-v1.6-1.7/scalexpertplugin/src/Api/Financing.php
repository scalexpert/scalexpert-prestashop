<?php
/**
 * Copyright © Scalexpert.
 * This file is part of Scalexpert plugin for PrestaShop. See COPYING.md for license details.
 *
 * @author    Scalexpert (https://scalexpert.societegenerale.com/)
 * @copyright Scalexpert
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */


namespace ScalexpertPlugin\Api;

use ScalexpertPlugin\Helper\BuyerFormatter;

class Financing extends Entity
{
    public static $scope = 'e-financing';
    public static $financindAmounts = [500, 1000];
    public static $buyerBillingCountry = ['FR', 'DE'];
    public static $financingStates = [
        'INITIALIZED',
        'REQUESTED',
        'PRE_ACCEPTED',
        'ACCEPTED',
        'REJECTED',
        'ABORTED',
        'CANCELLED'
    ];
    public static $finalFinancingStates = [
        'ACCEPTED',
        'REJECTED',
        'ABORTED',
        'CANCELLED'
    ];
    public static $excludedFinancingStates = [
        'REQUESTED'
    ];

    public static function getEligibleSolutionsForFront($financedAmount, $buyerBillingCountry)
    {
        $params = [
            [
                'financedAmount' => $financedAmount,
                'buyerBillingCountry' => $buyerBillingCountry
            ]
        ];

        $eligibleSolutions = [];
        foreach ($params as $param) {
            $apiUrl = static::$scope . '/api/v1/eligible-solutions?' . http_build_query($param);
            $result = Client::get($apiUrl);

            if (!$result['hasError']) {
                foreach ($result['data']['solutions'] as $solution) {
                    $eligibleSolutions[$solution['solutionCode']] = $solution;
                }
            }
        }

        if ($eligibleSolutions) {
            $financingSolutions = json_decode(\Configuration::get('SCALEXPERT_FINANCING_SOLUTIONS'), true);
            foreach ($eligibleSolutions as $key => $eligibleSolution) {
                if (empty($financingSolutions[$eligibleSolution['solutionCode']])) {
                    unset($eligibleSolutions[$key]);
                }
            }
        }

        return $eligibleSolutions;
    }

    public static function getSubscriptionInfo($subscriptionId)
    {
        $apiUrl = static::$scope . '/api/v1/subscriptions/' . (string)$subscriptionId;
        $result = Client::get($apiUrl);

        if (!$result['hasError']) {
            return $result['data'];
        }

        return $result;
    }

    public static function getFinancingSubscriptionsByOrderReference($merchantGlobalOrderId)
    {
        $params = [
            'merchantGlobalOrderId' => $merchantGlobalOrderId,
        ];

        $apiUrl = static::$scope . '/api/v1/subscriptions?' . http_build_query($params);
        $result = Client::get($apiUrl);

        if (!$result['hasError']) {
            return $result['data']['subscriptions'] ?? [];
        }

        return $result;
    }

    public static function getAllFinancingSubscriptions(): array
    {
        $params = [
            'pageSize' => 1000,
        ];

        $apiUrl = static::$scope . '/api/v1/subscriptions?' . http_build_query($params);
        $result = Client::get($apiUrl);

        if (!$result['hasError']) {
            return $result['data']['subscriptions'] ?? [];
        }

        return $result;
    }


    public static function createFinancingSubscription($order, $solutionCode): array
    {
        /* @var \Order $order */
        if (!\Validate::isLoadedObject($order)) {
            \Tools::redirect('index.php?controller=order&step=1');
        }

        /* @var \Customer $customer */
        $customer = new \Customer((int)$order->id_customer);
        if (!\Validate::isLoadedObject($customer)) {
            \Tools::redirect('index.php?controller=order&step=1');
        }

        // Prepare variables
        $shippingAddress = new \Address((int)$order->id_address_delivery);
        $billingAddress = new \Address((int)$order->id_address_invoice);
        $currency = new \Currency((int)$order->id_currency);
        $carrier = new \Carrier((int)$order->id_carrier);

        $orderItems = [];
        foreach ($order->getProducts() as $item) {
            $manufacturer = new \Manufacturer((int)$item['id_manufacturer']);
            $category = new \Category((int)$item['id_category_default'], 1);

            $itemData = [
                "id" => isset($item['product_id']) ? (string)$item['product_id'] : 'NC',
                "quantity" => isset($item['product_quantity']) ? (int)$item['product_quantity'] : 1,
                "model" => !empty($item['reference']) ? (string)$item['reference'] : 'NC',
                "label" => !empty($item['product_name']) ? trim(strip_tags(substr($item['product_name'], 0, 30))) : 'NC',
                "price" => isset($item['total_price_tax_incl']) ? (float)$item['total_price_tax_incl'] : 0,
                "currencyCode" => $currency->iso_code,
                "orderId" => (string)$order->reference,
                "brandName" => $manufacturer->name ?: 'NC',
                "description" => !empty($item['description']) ? trim(strip_tags($item['description'])) : 'NC',
                "specifications" => !empty($item['short_description']) ? trim(strip_tags($item['short_description'])) : 'NC',
                "category" => (string)substr(trim($category->name), 0, 20) ?: 'NC',
                "isFinanced" => true,
                "sku" => 'NC',
            ];
            $orderItems[] = $itemData;
        }

        $params = [
            'solutionCode' => $solutionCode,
            'merchantBasketId' => (string)$order->id_cart,
            'merchantGlobalOrderId' => (string)$order->reference,
            'merchantBuyerId' => (string)$customer->id,
            'financedAmount' => (float) \Tools::ps_round($order->total_paid, 2),
            'merchantUrls' => [
                'confirmation' => \Context::getContext()->link->getModuleLink('scalexpertplugin', 'confirmation', [
                    'order_ref' => $order->reference,
                    'secure_key' => $order->secure_key,
                ], true),
            ],
            'buyers' => [
                [
                    'billingContact' => BuyerFormatter::normalizeContact($billingAddress, $customer),
                    'billingAddress' => BuyerFormatter::normalizeAddress($billingAddress, 'BILLING_ADDRESS'),
                    'deliveryContact' => BuyerFormatter::normalizeContact($shippingAddress, $customer),
                    'deliveryAddress' => BuyerFormatter::normalizeAddress($shippingAddress, 'DELIVERY_ADDRESS'),
                    'contact' => BuyerFormatter::normalizeContact($shippingAddress, $customer),
                    'contactAddress' => BuyerFormatter::normalizeAddress($shippingAddress, 'MAIN_ADDRESS'),
                    "deliveryMethod" => $carrier->name ?: '',
                    "birthName" => !empty($customer->lastname) ? $customer->lastname : '',
                    "birthDate" => (!empty($customer->birthday) && '0000-00-00' !== $customer->birthday) ?
                        $customer->birthday : '1970-01-01',
                    "birthCityName" => '',
                    "vip" => false
                ]
            ],
            'basketDetails' => [
                'basketItems' => $orderItems
            ]
        ];

        $apiUrl = static::$scope . '/api/v1/subscriptions';
        return Client::post($apiUrl, $params);
    }

    public static function cancelFinancingSubscription(
        $creditSubscriptionId,
        $buyerFinancedAmount,
        $orderId
    ): array
    {
        $order = new \Order((int)$orderId);
        if (!\Validate::isLoadedObject($order)) {
            return [];
        }

        $customer = new \Customer((int)$order->id_customer);
        if (!\Validate::isLoadedObject($customer)) {
            return [];
        }

        $addreses = $customer->getAddresses($customer->id_lang);
        $address = end($addreses);
        $oAddress = isset($address['id_address']) ? new \Address((int) $address['id_address']) : null;
        if (!\Validate::isLoadedObject($oAddress)) {
            return [];
        }

        $params = [
            'cancelledAmount' => (float)$buyerFinancedAmount,
            'cancelledItem' => 'NC',
            'cancellationReason' => 'NC',
            'cancellationContact' => BuyerFormatter::normalizeCancelContact($customer, $oAddress),
        ];

        $apiUrl = static::$scope . '/api/v1/subscriptions/' . $creditSubscriptionId . '/_cancel';
        return Client::post($apiUrl, $params);
    }


    public static function confirmDeliveryFinancingSubscription(
        $creditSubscriptionId,
        $trackingNumber = '',
        $operator = '',
        $withFullPayload = true
    ): array
    {
        if ($withFullPayload) {
            $params = [
                'isDelivered' => true,
                'trackingNumber' => $trackingNumber,
                'operator' => $operator,
            ];
        } else {
            $params = [
                'isDelivered' => true,
            ];
        }

        $apiUrl = static::$scope . '/api/v1/subscriptions/' . $creditSubscriptionId . '/_confirmDelivery';
        return Client::post($apiUrl, $params);
    }
}
