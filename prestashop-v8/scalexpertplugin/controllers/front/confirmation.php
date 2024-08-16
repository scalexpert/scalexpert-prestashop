<?php
/**
 * Copyright © Scalexpert.
 * This file is part of Scalexpert plugin for PrestaShop. See COPYING.md for license details.
 *
 * @author    Scalexpert (https://scalexpert.societegenerale.com/)
 * @copyright Scalexpert
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

use Doctrine\ORM\EntityManager;
use PrestaShop\PrestaShop\Adapter\Presenter\Order\OrderPresenter;
use ScalexpertPlugin\Entity\ScalexpertOrderFinancing;
use ScalexpertPlugin\Handler\SolutionNameHandler;
use ScalexpertPlugin\Helper\API\Client;
use ScalexpertPlugin\Service\UpdateOrdersStatesService;

class ScalexpertpluginConfirmationModuleFrontController extends ModuleFrontController
{
    public $id_order;
    public $reference;

    protected $status = '';
    protected $title = '';
    protected $subtitle = '';
    protected $displayReorder = false;

    public function initContent()
    {
        parent::initContent();

        $this->setTemplate('module:scalexpertplugin/views/templates/front/orderConfirmation.tpl');
    }

    public function postProcess()
    {
        $cartId = Tools::getValue('cart_id');
        $orderId = Tools::getValue('order_id');
        $keyGet = Tools::getValue('k', '');

        if (
            empty($cartId)
            || empty($orderId)
            || empty($keyGet)
        ) {
            Tools::redirect('index.php');
        }

        $order = new Order((int) $orderId);
        $customer = new Customer((int) $order->id_customer);

        if (
            !Validate::isLoadedObject($order)
            || !Validate::isLoadedObject($customer)
        ) {
            Tools::redirect('index.php');
        }

        $this->id_order = $order->id;
        $this->reference = $order->reference;

        if (
            $keyGet === $order->secure_key
            && !$this->context->customer->isLogged()
        ) {
            $this->context->updateCustomer($customer);
            $this->context->cart->update();
        }

        if ($order->secure_key === $this->context->customer->secure_key) {
            sleep(2);
            $subscriptionStatus = $this->prepareConfirmationData($order);
            // Reload Order object with new state
            $order = new Order((int) $orderId);
        } else {
            $subscriptionStatus = '';
            $this->errors[] = $this->trans('An error occured. Please contact the merchant to have more informations', [], 'Modules.Scalexpertplugin.Shop');
        }

        $orderState = $order->getCurrentOrderState();
        $this->title = sprintf(
            '%s %s',
            $this->trans('Your order is', [], 'Modules.Scalexpertplugin.Shop'),
            $orderState->name[$this->context->language->id] ?? $this->trans('unknown', [], 'Modules.Scalexpertplugin.Shop')
        );

        if (
            !empty($subscriptionStatus)
            && !$this->context->customer->is_guest
            && in_array($subscriptionStatus, ['REJECTED', 'ABORTED'])
        ) {
            $this->displayReorder = true;
        }

        $this->context->smarty->assign(array(
            'link' => $this->context->link,
            'idOrder' => $this->id_order,
            'order' => (new OrderPresenter())->present($order),
            'reference' => $this->reference,
            'total' => Tools::displayPrice($order->total_paid),
            'reorderUrl' => \HistoryController::getUrlToReorder((int) $orderId, $this->context),
            'subscriptionStatus' => $subscriptionStatus,
            'subscriptionStatusFormatted' => $this->status,
            'subscriptionStatusTitle' => $this->title,
            'subscriptionStatusSubtitle' => $this->subtitle,
            'is_guest' => $this->context->customer->is_guest,
            'HOOK_ORDER_CONFIRMATION' => $this->displayOrderConfirmation($order),
            'HOOK_PAYMENT_RETURN' => $this->displayPaymentReturn($order),
            'displayReorder' => $this->displayReorder,
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

    protected function prepareConfirmationData(\Order $order)
    {
        try {
            /**
             * The order has been placed, so we redirect the customer on the confirmation page.
             * @var Client $apiClient
             * @var EntityManager $entityManager
             * @var SolutionNameHandler $solutionNameHandler
             */
            $apiClient = $this->get('scalexpert.api.client');
            $entityManager = $this->get('doctrine.orm.entity_manager');
            $solutionNameHandler = $this->get('scalexpert.handler.solution_name');
            $orderFinancingRepository = $entityManager->getRepository(ScalexpertOrderFinancing::class);
        } catch (\Exception $exception) {
            PrestaShopLogger::addLog($exception->getMessage());
            return '';
        }

        $orderFinancing = $orderFinancingRepository->findOneBy(['idOrder' => $order->id]);
        if ($orderFinancing) {
            $subscriptionInfo = $apiClient->getFinancingSubscriptionBySubscriptionId(
                $orderFinancing->getIdSubscription()
            );
        }

        if (isset($subscriptionInfo['consolidatedStatus'])) {
            // Update order state.
            /** @var UpdateOrdersStatesService $updateOrdersStatesService */
            $updateOrdersStatesService = $this->get('scalexpert.service.update_orders_states');
            $updateOrdersStatesService->updateOrderState(
                $order->reference,
                $subscriptionInfo['consolidatedStatus']
            );

            $this->status = $solutionNameHandler->getFinancialStateName(
                $subscriptionInfo['consolidatedStatus'],
                $this->module->getTranslator()
            );

            switch ($subscriptionInfo['consolidatedStatus']) {
                case 'ACCEPTED':
                    $this->subtitle = $this->trans('Your financing request has been accepted. A confirmation mail has been sent to you.', [], 'Modules.Scalexpertplugin.Shop');
                    break;
                case 'PRE_ACCEPTED':
                case 'INITIALIZED':
                case 'REQUESTED':
                    $this->subtitle = $this->trans('Your financing request has been sent to the lending organization and is being studied. You will soon receive an email informing you of the decision regarding this request.', [], 'Modules.Scalexpertplugin.Shop');
                    break;
                default:
                    $this->subtitle = $this->trans('We\'re sorry, your financing request was not accepted or a technical error occurred. We invite you to try again or place a new order by choosing another payment method.', [], 'Modules.Scalexpertplugin.Shop');
                    break;
            }
        } else {
            $this->status = $solutionNameHandler->getFinancialStateName('', $this->module->getTranslator());
            $this->subtitle = $this->trans('We\'re sorry, your financing request was not accepted or a technical error occurred. We invite you to try again or place a new order by choosing another payment method.', [], 'Modules.Scalexpertplugin.Shop');
        }

        return isset($subscriptionInfo) ? $subscriptionInfo['consolidatedStatus'] : '';
    }
}
