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

class ScalexpertPluginConfirmationModuleFrontController extends ModuleFrontController
{
    public $id_order;
    public $reference;
    public $id_module;

    /** For PrestaShop 1.6 **/
    public $display_column_left = false;
    public $display_column_right = false;

    public function initContent()
    {
        parent::initContent();

        if (version_compare(_PS_VERSION_, '1.7', '>=')) {
            $this->setTemplate('module:scalexpertplugin/views/templates/front/ps17/orderConfirmation.tpl');
        } else {
            $this->setTemplate('ps16/orderConfirmation.tpl');
            $this->context->controller->addCSS(_PS_MODULE_DIR_ . 'scalexpertplugin/views/css/ps16/frontOrderConfirmation.css');
        }
    }

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

        $this->id_order = $order->id;
        $this->reference = $order->reference;
        $this->id_module = $this->module->id;

        if ($secure_key === $customer->secure_key) {

            sleep(2);

            /**
             * The order has been placed, so we redirect the customer on the confirmation page.
             */
            $idSubscription = FinancingOrder::get($this->id_order);
            $subscriptionInfo = Financing::getSubscriptionInfo($idSubscription);

            if (
                isset($subscriptionInfo['consolidatedStatus'])
                && $this->updateOrderState($orders, $subscriptionInfo['consolidatedStatus'])
            ) {
                $status = $this->module->getFinancialStateName($subscriptionInfo['consolidatedStatus']);

                switch ($subscriptionInfo['consolidatedStatus']) {
                    case 'ACCEPTED':
                        $title = $this->module->l('Your orders is paid');
                        $subtitle = $this->module->l('Your financing request has been accepted. A confirmation mail has been sent to you.');
                        break;
                    case 'PRE_ACCEPTED':
                    case 'INITIALIZED':
                    case 'REQUESTED':
                        $title = $this->module->l('Your orders are awaiting financing');
                        $subtitle = $this->module->l('Your financing request has been sent to the lending organization and is being studied. You will soon receive an email informing you of the decision regarding this request.');
                        break;
                    default:
                        $title = $this->module->l('Your orders is canceled');
                        $subtitle = $this->module->l('We\'re sorry, your financing request was not accepted or a technical error occurred. We invite you to try again or place a new order by choosing another payment method.');
                        break;
                }
            } else {
                $status = $this->module->getFinancialStateName('');
                $title = $this->module->l('Your orders is canceled');
                $subtitle = $this->module->l('We\'re sorry, your financing request was not accepted or a technical error occurred. We invite you to try again or place a new order by choosing another payment method.');
            }
        }

        if (version_compare(_PS_VERSION_, '1.7', '>=')) {
            $orderPresenter = new PrestaShop\PrestaShop\Adapter\Order\OrderPresenter();
            $presentedOrder = $orderPresenter->present($order);
        } else {
            $presentedOrder = $order;
        }

        $this->context->smarty->assign(array(
            'id_order' => $this->id_order,
            'order' => $presentedOrder,
            'reference' => $this->reference,
            'total' => Tools::displayPrice($order->total_paid),
            'subscription_status' => $subscriptionInfo['consolidatedStatus'] ?? '',
            'subscription_status_formatted' => $status ?? '',
            'subscription_status_title' => $title ?? '',
            'subscription_status_subtitle' => $subtitle ?? '',
            'is_guest' => $this->context->customer->is_guest,
        ));

        if ($this->context->customer->is_guest) {
            $this->context->smarty->assign(array(
                'id_order_formatted' => sprintf('#%06d', $this->id_order),
                'email' => $this->context->customer->email
            ));
            /* If guest we clear the cookie for security reason */
            $this->context->customer->mylogout();
        }
    }

    /**
     * Execute the hook displayPaymentReturn
     */
    public function displayPaymentReturn()
    {
        if (Validate::isUnsignedId($this->id_order) && Validate::isUnsignedId($this->id_module))
        {
            $params = array();
            $order = new Order($this->id_order);
            $currency = new Currency($order->id_currency);

            if (Validate::isLoadedObject($order))
            {
                $params['total_to_pay'] = $order->getOrdersTotalPaid();
                $params['currency'] = $currency->sign;
                $params['objOrder'] = $order;
                $params['currencyObj'] = $currency;

                return Hook::exec('displayPaymentReturn', $params, $this->id_module);
            }
        }
        return false;
    }

    /**
     * Execute the hook displayOrderConfirmation
     */
    public function displayOrderConfirmation()
    {
        if (Validate::isUnsignedId($this->id_order))
        {
            $params = array();
            $order = new Order($this->id_order);
            $currency = new Currency($order->id_currency);

            if (Validate::isLoadedObject($order))
            {
                $params['total_to_pay'] = $order->getOrdersTotalPaid();
                $params['currency'] = $currency->sign;
                $params['objOrder'] = $order;
                $params['currencyObj'] = $currency;

                return Hook::exec('displayOrderConfirmation', $params);
            }
        }
        return false;
    }

    protected function handleError(string $message = '')
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

    protected function updateOrderState($orderCollection, $consolidatedStatus)
    {
        foreach ($orderCollection as $order) {
            if (!Validate::isLoadedObject($order)) {
                return false;
            }

            try {
                $this->module->updateOrderStateBasedOnFinancingStatus($order, $consolidatedStatus);
            } catch (\Exception $e) {
                return false;
            }
        }

        return true;
    }
}
