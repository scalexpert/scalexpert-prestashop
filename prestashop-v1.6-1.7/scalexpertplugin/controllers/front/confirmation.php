<?php
/**
 * Copyright © Scalexpert.
 * This file is part of Scalexpert plugin for PrestaShop.
 *
 * @author    Société Générale
 * @copyright Scalexpert
 */
class ScalexpertPluginConfirmationModuleFrontController extends ModuleFrontController
{
    public function postProcess()
    {
        $order_ref = Tools::getValue('order_ref');
        $secure_key = Tools::getValue('secure_key');
        if (
            false === $order_ref
            || false === $secure_key
        ) {
            $this->handleError($this->module->l('Some parameters are missing.'));
        }

        $orders = Order::getByReference($order_ref);
        if (1 > $orders->count()) {
            $this->handleError($this->module->l('No order found for this reference.'));
        }

        $order = $orders->getFirst();
        if (
            !Validate::isLoadedObject($order)
            || $secure_key !== $order->secure_key
        ) {
            $this->handleError($this->module->l('No order found for this reference.'));
        }

        $customer = \Context::getContext()->customer;
        if (
            !Validate::isLoadedObject($customer)
            || !$customer->isLogged()
        ) {
            $customer = $this->authenticateCustomerById((int) $order->id_customer);
        }

        if (!Validate::isLoadedObject($customer)) {
            $this->handleError($this->module->l('No customer found.'));
        }

        if ($secure_key === $customer->secure_key) {

            sleep(2);

            /**
             * The order has been placed so we redirect the customer on the confirmation page.
             */
            $module_id = $this->module->id;
            Tools::redirect('index.php?controller=order-confirmation&id_cart=' . $order->id_cart . '&id_module=' . $module_id . '&id_order=' . $order->id . '&key=' . $secure_key);
        } else {
            $this->handleError($this->module->l('An error occured. Please contact the merchant to have more informations'));
        }
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

    protected function authenticateCustomerById($idCustomer)
    {
        $customer = new Customer((int) $idCustomer);

        if (version_compare(_PS_VERSION_, '1.7', '>=')) {
            $this->context->updateCustomer($customer);
            $this->context->cart->update();
        } else {
            Hook::exec('actionBeforeAuthentication');

            $this->context->cookie->id_customer = $customer->id;
            $this->context->cookie->customer_lastname = $customer->lastname;
            $this->context->cookie->customer_firstname = $customer->firstname;
            $this->context->cookie->logged = 1;
            $customer->logged = 1;
            $this->context->cookie->is_guest = $customer->isGuest();
            $this->context->cookie->passwd = $customer->passwd;
            $this->context->cookie->email = $customer->email;

            // Add customer to the context
            $this->context->customer = $customer;
            $id_cart = (int)Cart::lastNoneOrderedCart($this->context->customer->id);

            if ($id_cart) {
                $this->context->cart = new Cart($id_cart);
            }

            $this->context->cart->id_customer = (int)$customer->id;
            $this->context->cart->secure_key = $customer->secure_key;
            $id_carrier = null;
            if (
                $this->ajax
                && isset($id_carrier)
                && $id_carrier
                && Configuration::get('PS_ORDER_PROCESS_TYPE')
            ) {
                $delivery_option = array($this->context->cart->id_address_delivery => $id_carrier . ',');
                $this->context->cart->setDeliveryOption($delivery_option);
            }

            $this->context->cart->save();
            $this->context->cookie->id_cart = (int)$this->context->cart->id;
            $this->context->cookie->write();
            $this->context->cart->autosetProductAddress();

            Hook::exec('actionAuthentication');

            // Login information have changed, so we check if the cart rules still apply
            CartRule::autoRemoveFromCart($this->context);
            CartRule::autoAddToCart($this->context);
        }

        return $this->context->customer;
    }
}
