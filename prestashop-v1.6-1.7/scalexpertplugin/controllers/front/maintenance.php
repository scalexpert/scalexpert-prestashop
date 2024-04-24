<?php
/**
 * Copyright © Scalexpert.
 * This file is part of Scalexpert plugin for PrestaShop. See COPYING.md for license details.
 *
 * @author    Scalexpert (https://scalexpert.societegenerale.com/)
 * @copyright Scalexpert
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */


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

    protected $mappingOrderState = [];

    public function __construct()
    {
        $this->logger = new Logger();

        parent::__construct();

        $this->loadMappingOrderState();
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

        $this->ajaxDie('Cron OK');
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

            $previousOrderStateList = $order->getHistory(Context::getContext()->language->id);
            $orderStateAlreadyOnOrder = false;
            if (isset($this->mappingOrderState[$consolidatedStatus])) {
                foreach ($previousOrderStateList as $previousOrderState) {
                    if ((int)$previousOrderState['id_order_state'] == (int)$this->mappingOrderState[$consolidatedStatus]) {
                        $orderStateAlreadyOnOrder = true;
                    }
                }
            }
            if ($orderStateAlreadyOnOrder) {
                $this->logAndPrint("Order state is already on order for #$order->reference:<br>");
                return;
            }

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

    private function loadMappingOrderState()
    {
        $orderStateMapping = json_decode(Configuration::get('SCALEXPERT_ORDER_STATE_MAPPING'), true);
        if (!empty($orderStateMapping)) {
            $this->mappingOrderState = $orderStateMapping;
        }
    }
}
