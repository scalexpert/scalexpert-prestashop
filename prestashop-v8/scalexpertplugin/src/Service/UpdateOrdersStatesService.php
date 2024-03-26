<?php
/**
 * Copyright © Scalexpert.
 * This file is part of Scalexpert plugin for PrestaShop. See COPYING.md for license details.
 *
 * @author    Scalexpert (https://scalexpert.societegenerale.com/)
 * @copyright Scalexpert
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

namespace ScalexpertPlugin\Service;

use ScalexpertPlugin\Helper\API\Client;

class UpdateOrdersStatesService
{
    private $apiClient;

    /**
     * @var OrderUpdaterService
     */
    private $orderUpdaterService;

    public function __construct(
        Client $apiClient,
        OrderUpdaterService $orderUpdaterService
    )
    {
        $this->apiClient = $apiClient;
        $this->orderUpdaterService = $orderUpdaterService;
    }

    public function updateOrdersStates()
    {
        $financingSubscriptions = $this->apiClient->getAllFinancingSubscriptions();

        if (!empty($financingSubscriptions)) {
            foreach ($financingSubscriptions as $financingSubscription) {
                if (!empty($financingSubscription['merchantGlobalOrderId'])
                    && !empty($financingSubscription['consolidatedStatus'])
                ) {
                    $this->updateOrderState(
                        $financingSubscription['merchantGlobalOrderId'],
                        $financingSubscription['consolidatedStatus']
                    );
                }
            }
        }
    }

    public function updateOrderState($merchantGlobalOrderId, $consolidatedStatus): void
    {
        $ordersCollection = \Order::getByReference($merchantGlobalOrderId);

        if (count($ordersCollection) > 0) {
            foreach ($ordersCollection as $order) {
                try {
                    $this->orderUpdaterService->updateOrderStateBasedOnFinancingStatus($order, $consolidatedStatus);
                } catch (\Exception $e) {
                    \PrestaShopLogger::addLog(
                        '[SCALEXPERTPLUGIN] Error while updateOrderState: '.$e->getMessage(),
                        3,
                        null,
                        'Order',
                        $order->id,
                        true
                    );
                }
            }
        }
    }
}

