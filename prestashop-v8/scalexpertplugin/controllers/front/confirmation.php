<?php
/**
 * Copyright © Scalexpert.
 * This file is part of Scalexpert plugin for PrestaShop.
 *
 * @author    Société Générale
 * @copyright Scalexpert
 */

class ScalexpertpluginConfirmationModuleFrontController extends ModuleFrontController
{
    public function postProcess()
    {
        $cartId = Tools::getValue('cart_id');
        $orderId = Tools::getValue('order_id');
        $keyGet = Tools::getValue('k');

        if (empty($cartId) || empty($orderId) || empty($keyGet)) {
            Tools::redirect('index.php');
        }

        $order = new Order((int) $orderId);
        $customer = new Customer((int) $order->id_customer);

        if (!Validate::isLoadedObject($order) || !Validate::isLoadedObject($customer)) {
            Tools::redirect('index.php');
        }

        if (!$this->context->customer->isLogged() && $keyGet == $order->secure_key) {
            $this->context->updateCustomer($customer);
            $this->context->cart->update();
        }

        if ($order->secure_key == $this->context->customer->secure_key) {
            sleep(2);

            Tools::redirect($this->context->link->getPageLink(
                'order-confirmation',
                true,
                (int) $this->context->language->id,
                [
                    'id_cart' => (int) $order->id_cart,
                    'id_module' => (int) $this->module->id,
                    'id_order' => (int) $order->id,
                    'key' => $customer->secure_key,
                ]
            ));
        } else {
            $this->errors[] = $this->module->l('An error occured. Please contact the merchant to have more informations');
        }

        Tools::redirect('index.php');
    }
}
