<?php

use ScalexpertPlugin\Model\FinancingOrder;

/**
 * Copyright © Scalexpert.
 * This file is part of Scalexpert plugin for PrestaShop.
 *
 * @author    Société Générale
 * @copyright Scalexpert
 */
class ScalexpertPluginValidationModuleFrontController extends ModuleFrontController
{
    /**
     * This class should be use by your Instant Payment
     * Notification system to validate the order remotely
     */
    public function postProcess()
    {
        /*
         * If the module is not active anymore, no need to process anything.
         */
        $cart = $this->context->cart;
        if (
            $cart->id_customer == 0
            || $cart->id_address_delivery == 0
            || $cart->id_address_invoice == 0
        ) {
            $this->handleError($this->module->l('Cart is invalid.'));
        }

        if (!$this->module->active) {
            $this->handleError($this->module->l('Payment method is disabled.'));
        }

        $customer = new Customer($cart->id_customer);
        if (!Validate::isLoadedObject($customer)) {
            $this->handleError($this->module->l('No customer found.'));
        }

        $secure_key = Context::getContext()->customer->secure_key;

        if ($this->isValidOrder() === true) {
            $payment_status = Configuration::get('SCALEXPERT_ORDER_STATE_WAITING');
            $message = null;
        } else {
            $payment_status = Configuration::get('PS_OS_ERROR');
            $message = $this->module->l($this->module->l('An error occurred while processing payment'));
        }

        $module_name = $this->module->displayName;
        $currency_id = (int) Context::getContext()->currency->id;
        $solutionCode = Tools::getValue('solutionCode');
        if (!$solutionCode) {
            $this->handleError($this->module->l('You must chose a financial solution.'));
        }

        $validateOrder = $this->module->validateOrder(
            $this->context->cart->id,
            $payment_status,
            $this->context->cart->getOrderTotal(),
            $this->module->getSolutionDisplayName($solutionCode),
            $message,
            array(),
            $currency_id,
            false,
            $secure_key
        );
        $newOrder = new \Order($this->module->currentOrder);

        if (
            $validateOrder
            && \Validate::isLoadedObject($newOrder)
        ) {
            $subscription = ScalexpertPlugin\Api\Financing::createFinancingSubscription(
                $newOrder,
                $solutionCode
            );

            if (!$subscription['hasError']) {

                FinancingOrder::save(
                    $newOrder->id,
                    $subscription['data']['id'] ?? ''
                );

                if (isset($subscription['data']['redirect']['value'])) {
                    \Tools::redirect($subscription['data']['redirect']['value']);
                } else {
                    $this->handleError(
                        sprintf(
                            $this->module->l("Can't redirect to payment platform %s"),
                            $module_name
                        )
                    );
                }
            } else {
                $this->handleError($this->module->l('An error occured during financing subscription.'));
            }
        }

        return $validateOrder;
    }

    protected function isValidOrder()
    {
        /*
         * Add your checks right there
         */
        return true;
    }

    protected function handleError(string $message = ''): void
    {
        if (
            version_compare(_PS_VERSION_, '1.7', '>=')
            && !empty($message)
        ) {
            $this->errors[] = $message;
            $this->redirectWithNotifications(
                $this->context->link->getPageLink('cart', null, null, ['action' => 'show'])
            );
        } else {
            \Tools::redirect('index.php?controller=order&step=1');
        }
    }
}
