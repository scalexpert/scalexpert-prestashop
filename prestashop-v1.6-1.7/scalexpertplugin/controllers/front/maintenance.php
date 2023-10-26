<?php
/**
 * Copyright © Scalexpert.
 * This file is part of Scalexpert plugin for PrestaShop.
 *
 * @author    Société Générale
 * @copyright Scalexpert
 */

class ScalexpertPluginMaintenanceModuleFrontController extends ModuleFrontController
{
    /**
     * Do whatever you have to before redirecting the customer on the website of your payment processor.
     */
    public function postProcess()
    {
        $financingSubscriptions = DATASOLUTION\Module\Scalexpert\Api\Financing::getAllFinancingSubscriptions();
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
            echo("No order found for merchantGlobalOrderId: $merchantGlobalOrderId<br>");
            return;
        }

        foreach ($orderCollection as $k => $order) {
            if (!Validate::isLoadedObject($order)) {
                echo("Can't load order $k in collection for merchantGlobalOrderId: $merchantGlobalOrderId<br>");
                continue;
            }

            echo("Order #$order->reference in progress<br>");
            $orderState = $this->module->getOrderSateByApiState($consolidatedStatus);

            if (
                null !== $orderState
                && (int)$orderState->id !== (int)$order->current_state
            ) {
                try {
                    $order->setCurrentState($orderState->id);
                    echo("Successfull order state update for #$order->reference<br>");
                } catch (Exception $e) {
                    echo("Error during order state update for #$order->reference:<br>");
                    echo("$e->getMessage()<br>");
                }
            } else {
                echo("No order state update for #$order->reference<br>");
            }
        }
    }
}
