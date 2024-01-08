<?php

use ScalexpertPlugin\Api\Financing;
use ScalexpertPlugin\Log\Logger;

/**
 * Copyright © Scalexpert.
 * This file is part of Scalexpert plugin for PrestaShop.
 *
 * @author    Société Générale
 * @copyright Scalexpert
 */

class ScalexpertPluginMaintenanceModuleFrontController extends ModuleFrontController
{
    protected $logger;

    public function __construct()
    {
        $this->logger = new Logger();

        parent::__construct();
    }

    /**
     * Do whatever you have to before redirecting the customer on the website of your payment processor.
     */
    public function postProcess()
    {
        $financingSubscriptions = Financing::getAllFinancingSubscriptions();
        if (!empty($financingSubscriptions)) {
            foreach ($financingSubscriptions as $financingSubscription) {
                if (
                    !empty($financingSubscription['merchantGlobalOrderId'])
                    && !empty($financingSubscription['consolidatedStatus'])
                ) {
                    $this->updateOrderState(
                        $financingSubscription['merchantGlobalOrderId'],
                        $financingSubscription['consolidatedStatus']
                    );
                }
            }
        }

        die('Cron OK');
    }

    protected function updateOrderState($merchantGlobalOrderId, $consolidatedStatus)
    {
        $orderCollection = Order::getByReference($merchantGlobalOrderId);
        if (1 > $orderCollection->count()) {
            $this->logAndPrint("No order found for merchantGlobalOrderId: $merchantGlobalOrderId<br>");
            return;
        }

        foreach ($orderCollection as $k => $order) {
            if (!Validate::isLoadedObject($order)) {
                $this->logAndPrint("Can't load order $k in collection for merchantGlobalOrderId: $merchantGlobalOrderId<br>");
                continue;
            }

            $this->logAndPrint("Order #$order->reference in progress<br>");

            try {
                $this->module->updateOrderStateBasedOnFinancingStatus($order, $consolidatedStatus);
                $this->logAndPrint("Successfull order state update for #$order->reference<br>");
            } catch (\Exception $e) {
                $this->logAndPrint("Error during order state update for #$order->reference:<br>");
                $this->logAndPrint($e->getMessage());
            }
        }
    }

    private function logAndPrint($message) {
        $this->logger->logInfo("[MAINTENANCE CRON] $message");
        echo "$message <br>";
    }
}
