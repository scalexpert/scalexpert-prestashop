<?php

use PrestaShop\PrestaShop\Adapter\Presenter\Order\OrderPresenter;
use ScalexpertPlugin\Entity\ScalexpertOrderFinancing;
use ScalexpertPlugin\Service\UpdateOrdersStatesService;

/**
 * Copyright © Scalexpert.
 * This file is part of Scalexpert plugin for PrestaShop.
 *
 * @author    Société Générale
 * @copyright Scalexpert
 */

class ScalexpertpluginConfirmationModuleFrontController extends ModuleFrontController
{
    public $id_order;
    public $reference;

    public function initContent()
    {
        parent::initContent();

        $this->setTemplate('module:scalexpertplugin/views/templates/front/orderConfirmation.tpl');
    }

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

        $this->id_order = $order->id;
        $this->reference = $order->reference;

        if (!$this->context->customer->isLogged() && $keyGet == $order->secure_key) {
            $this->context->updateCustomer($customer);
            $this->context->cart->update();
        }

        $displayReorder = false;
        if ($order->secure_key == $this->context->customer->secure_key) {
            sleep(2);

            /*Tools::redirect($this->context->link->getPageLink(
                'order-confirmation',
                true,
                (int) $this->context->language->id,
                [
                    'id_cart' => (int) $order->id_cart,
                    'id_module' => (int) $this->module->id,
                    'id_order' => (int) $order->id,
                    'key' => $customer->secure_key,
                ]
            ));*/

            /**
             * The order has been placed, so we redirect the customer on the confirmation page.
             */
            $entityManager = $this->get('doctrine.orm.entity_manager');
            $apiClient = $this->get('scalexpert.api.client');
            $orderFinancingRepository = $entityManager->getRepository(ScalexpertOrderFinancing::class);

            $orderFinancing = $orderFinancingRepository->findOneBy(['idOrder' => $orderId]);
            if ($orderFinancing) {
                $subscriptionInfo = $apiClient->getFinancingSubscriptionBySubscriptionId(
                    $orderFinancing->getIdSubscription()
                );
            }

            $status = $this->module->l('Unknown state');
            $title = '';
            $subtitle = '';
            if (isset($subscriptionInfo) && isset($subscriptionInfo['consolidatedStatus'])) {

                // Update order state.
                /** @var UpdateOrdersStatesService $updateOrdersStatesService */
                $updateOrdersStatesService = $this->get('scalexpert.service.update_orders_states');
                try {
                    $updateOrdersStatesService->updateOrderState($order->reference, $subscriptionInfo['consolidatedStatus']);
                } catch (Exception $e) {
                }

                $status = $this->module->getFinancialStateName($subscriptionInfo['consolidatedStatus']);

                switch ($subscriptionInfo['consolidatedStatus']) {
                    case 'ACCEPTED':
                        $title = $this->trans('Your orders is paid', [], 'Modules.Scalexpertplugin.Shop');
                        $subtitle = $this->trans('Your financing request has been accepted. A confirmation mail has been sent to you.', [], 'Modules.Scalexpertplugin.Shop');
                        break;
                    case 'PRE_ACCEPTED':
                    case 'INITIALIZED':
                    case 'REQUESTED':
                        $title = $this->trans('Your orders are awaiting financing', [], 'Modules.Scalexpertplugin.Shop');
                        $subtitle = $this->trans('Your financing request has been sent to the lending organization and is being studied. You will soon receive an email informing you of the decision regarding this request.', [], 'Modules.Scalexpertplugin.Shop');
                        break;
                    case 'REJECTED':
                    case 'CANCELLED':
                    case 'ABORTED':
                        $title = $this->trans('Your orders is canceled', [], 'Modules.Scalexpertplugin.Shop');
                        $subtitle = $this->trans('We\'re sorry, your financing request was not accepted or a technical error occurred. We invite you to try again or place a new order by choosing another payment method.', [], 'Modules.Scalexpertplugin.Shop');
                        $displayReorder = true;
                        break;
                }
            } else {
                $this->errors[] = $this->trans('An error occured. Please contact the merchant to have more informations', [], 'Modules.Scalexpertplugin.Shop');
            }
        } else {
            $this->errors[] = $this->trans('An error occured. Please contact the merchant to have more informations', [], 'Modules.Scalexpertplugin.Shop');
        }

        $this->context->smarty->assign(array(
            'link' => $this->context->link,
        ));

        $this->context->smarty->assign(array(
            'idOrder' => $this->id_order,
            'order' => (new OrderPresenter())->present($order),
            'reference' => $this->reference,
            'total' => Tools::displayPrice($order->total_paid),
            'reorderUrl' => \HistoryController::getUrlToReorder((int) $orderId, $this->context),
            'subscriptionStatus' => isset($subscriptionInfo) ? $subscriptionInfo['consolidatedStatus'] : '',
            'subscriptionStatusFormatted' => $status ?? '',
            'subscriptionStatusTitle' => $title ?? '',
            'subscriptionStatusSubtitle' => $subtitle ?? '',
            'is_guest' => $this->context->customer->is_guest,
            'HOOK_ORDER_CONFIRMATION' => $this->displayOrderConfirmation($order),
            'HOOK_PAYMENT_RETURN' => $this->displayPaymentReturn($order),
            'displayReorder' => $displayReorder,
        ));

        if ($this->context->customer->is_guest) {
            $this->context->smarty->assign(array(
                'id_order_formatted' => sprintf('#%06d', $this->id_order),
                'email' => $this->context->customer->email
            ));
            /* If guest we clear the cookie for security reason */
            $this->context->customer->mylogout();
        }

//        Tools::redirect('index.php');
    }

    public function displayOrderConfirmation($order)
    {
        return Hook::exec('displayOrderConfirmation', ['order' => $order]);
    }

    public function displayPaymentReturn($order)
    {
        if (!Validate::isUnsignedId($this->module->id)) {
            return false;
        }

        return Hook::exec('displayPaymentReturn', ['order' => $order], $this->module->id);
    }
}
