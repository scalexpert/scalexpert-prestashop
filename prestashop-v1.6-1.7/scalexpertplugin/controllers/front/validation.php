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
use ScalexpertPlugin\Model\FinancingOrder;


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

        $address = new Address((int) $cart->id_address_delivery);
        if (Validate::isLoadedObject($address)) {
            $phone = !empty($address->phone_mobile) ? $address->phone_mobile : $address->phone;
            if (!preg_match('/^\+?(?:[0-9] ?){6,14}[0-9]$/', $phone)) {
                $this->handleError($this->module->l('Please provide a valid phone number to select this payment method'));
            }
        }

        $secure_key = Context::getContext()->customer->secure_key;
        $currency_id = (int) Context::getContext()->currency->id;

        if ($this->isValidOrder() === true) {
            $payment_status = Configuration::get('SCALEXPERT_ORDER_STATE_WAITING');
            $message = null;
        } else {
            $payment_status = Configuration::get('PS_OS_ERROR');
            $message = $this->module->l('An error occurred while processing payment');
        }

        $solutionCode = Tools::getValue('solutionCode');
        if (!$solutionCode) {
            $this->handleError($this->module->l('You must chose a financial solution.'));
        }

        $solutionName = '';
        $financingSolutions = Financing::getEligibleSolutions();
        if (
            !empty($financingSolutions)
            && !empty($financingSolutions[$solutionCode]['communicationKit']['visualTitle'])
        ) {
            $solutionName = strip_tags($financingSolutions[$solutionCode]['communicationKit']['visualTitle']);
        }

        $validateOrder = $this->module->validateOrder(
            $this->context->cart->id,
            $payment_status,
            $this->context->cart->getOrderTotal(),
            $solutionName,
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
            try {
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
                    }
                }

                $this->redirectWithError($newOrder);
            } catch (\Exception $exception) {
                $this->redirectWithError($newOrder);
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

    protected function redirectWithError($order): void
    {
        // Update order state
        $order->setCurrentState(Configuration::get('PS_OS_ERROR'));

        // Generate redirect url
        $redirect = \Context::getContext()->link->getModuleLink('scalexpertplugin', 'confirmation', [
            'order_ref' => $order->reference,
            'secure_key' => $order->secure_key,
        ], true);
        \Tools::redirect($redirect);
    }

    protected function handleError(string $message = ''): void
    {
        if (version_compare(_PS_VERSION_, '1.7', '>=')) {
            $this->errors[] = $message;
            $this->redirectWithNotifications(
                $this->context->link->getPageLink('order', null, null)
            );
        } else {
            \Tools::redirect('index.php?controller=order&step=1&phoneError=1');
        }
    }
}
