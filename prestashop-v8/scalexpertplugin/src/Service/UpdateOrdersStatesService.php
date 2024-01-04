<?php

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
        OrderUpdaterService $orderUpdaterService,
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

    public function updateOrderState($merchantGlobalOrderId, $consolidatedStatus)
    {
        $ordersCollection = \Order::getByReference($merchantGlobalOrderId);

        if (count($ordersCollection) > 0) {
            foreach ($ordersCollection as $order) {
                $this->orderUpdaterService->updateOrderStateBasedOnFinancingStatus($order, $consolidatedStatus);
            }
        }
    }
}

