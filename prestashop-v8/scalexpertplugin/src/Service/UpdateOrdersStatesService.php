<?php
/**
 * Copyright © Scalexpert.
 * This file is part of Scalexpert plugin for PrestaShop.
 *
 * @author    Société Générale
 * @copyright Scalexpert
 */

declare(strict_types=1);

namespace ScalexpertPlugin\Service;

use ScalexpertPlugin\Helper\API\Client;

class UpdateOrdersStatesService
{
    private $apiClient;

    private $orderStateHandler;

    public function __construct(Client $apiClient, $orderStateHandler)
    {
        $this->apiClient = $apiClient;
        $this->orderStateHandler = $orderStateHandler;
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

    public function updateOrderState($merchantGlobalOrderId, $consolidatedStatus)
    {
        $ordersCollection = \Order::getByReference($merchantGlobalOrderId);

        if (count($ordersCollection) > 0) {
            foreach ($ordersCollection as $order) {
                if (\Validate::isLoadedObject($order)) {
                    $orderStateId = $this->orderStateHandler->getIdOrderStateByApiStatus($consolidatedStatus);

                    if (!empty($orderStateId) && $orderStateId != $order->current_state) {
                        $order->setCurrentState($orderStateId);
                    }
                }
            }
        }
    }
}