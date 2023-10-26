<?php

use ScalexpertPlugin\Entity\ScalexpertOrderFinancing;

/**
 * Copyright © Scalexpert.
 * This file is part of Scalexpert plugin for PrestaShop.
 *
 * @author    Société Générale
 * @copyright Scalexpert
 */

class ScalexpertpluginValidationModuleFrontController extends ModuleFrontController
{
    public function postProcess()
    {
        if (!($this->module instanceof ScalexpertPlugin)) {
            Tools::redirect('index.php?controller=order&step=1');
            return;
        }

        $cart = $this->context->cart;

        if ($cart->id_customer == 0 || $cart->id_address_delivery == 0 || $cart->id_address_invoice == 0 || !$this->module->active) {
            Tools::redirect('index.php?controller=order&step=1');
            return;
        }

        // Check that this payment option is still available in case the customer changed his address just before the end of the checkout process
        $authorized = false;

        foreach (Module::getPaymentModules() as $module) {
            if ($module['name'] == 'scalexpertplugin') {
                $authorized = true;
                break;
            }
        }

        if (!$authorized) {
            exit($this->trans('This payment method is not available.', [], 'Modules.Scalexpertplugin.Shop'));
        }

        $customer = new Customer($cart->id_customer);

        if (!Validate::isLoadedObject($customer)) {
            Tools::redirect('index.php?controller=order&step=1');
            return;
        }

        $currency = $this->context->currency;
        $total = (float) $cart->getOrderTotal();

        $validateOrder = $this->module->validateOrder(
            (int) $cart->id,
            (int) Configuration::get(ScalexpertPlugin::CONFIGURATION_ORDER_STATE_FINANCING),
            $total,
            $this->module->displayName,
            null,
            [],
            (int) $currency->id,
            false,
            $customer->secure_key
        );

        $apiClient = $this->get('scalexpert.api.client');
        $currentOrder = new Order($this->module->currentOrder);
        $solutionCode = Tools::getValue('solutionCode');

        if ($validateOrder && Validate::isLoadedObject($currentOrder)) {
            $financingSubscription = $apiClient->createFinancingSubscription($currentOrder, $solutionCode);

            if (!empty($financingSubscription['id'])) {
                $entityManager = $this->get('doctrine.orm.entity_manager');
                $orderFinancing = new ScalexpertOrderFinancing();
                $orderFinancing->setIdOrder((int) $currentOrder->id);
                $orderFinancing->setIdSubscription((string) $financingSubscription['id']);
                $entityManager->persist($orderFinancing);
                $entityManager->flush();

                Tools::redirect($financingSubscription['redirect']['value']);
            }
        }

        // Error during subscription creation
        $this->errors[] = $this->trans('An error occurred during financing subscription.', [], 'Modules.Scalexpertplugin.Shop');
        $this->redirectWithNotifications($this->context->link->getPageLink('cart', null, null, ['action' => 'show']));
    }
}