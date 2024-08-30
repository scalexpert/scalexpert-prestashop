<?php
/**
 * Copyright © Scalexpert.
 * This file is part of Scalexpert plugin for PrestaShop. See COPYING.md for license details.
 *
 * @author    Scalexpert (https://scalexpert.societegenerale.com/)
 * @copyright Scalexpert
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

use ScalexpertPlugin\Entity\ScalexpertOrderFinancing;


class ScalexpertpluginValidationModuleFrontController extends ModuleFrontController
{
    public function postProcess()
    {
        $cart = $this->context->cart;
        $customer = new Customer($cart->id_customer);

        if (!$this->checkBeforeValidation($cart, $customer)) {
            return;
        }

        $currency = $this->context->currency;
        $total = (float)$cart->getOrderTotal();

        $solutionCode = Tools::getValue('solutionCode');
        $availableSolutionsService = $this->get('scalexpert.service.available_solutions');
        $availableFinancialSolutions = $availableSolutionsService->getAvailableFinancialSolutions();
        $solutionName = '';
        foreach ($availableFinancialSolutions as $availableFinancialSolution) {
            if ($solutionCode === $availableFinancialSolution['solutionCode']) {
                $solutionName = strip_tags(
                    $availableFinancialSolution['visualTitle']
                );
            }
        }

        $validateOrder = $this->module->validateOrder(
            (int)$cart->id,
            (int)Configuration::get(ScalexpertPlugin::CONFIGURATION_ORDER_STATE_FINANCING),
            $total,
            $solutionName,
            null,
            [],
            (int)$currency->id,
            false,
            $customer->secure_key
        );

        $currentOrder = new Order($this->module->currentOrder);
        if ($validateOrder && Validate::isLoadedObject($currentOrder)) {
            try {
                /* @var \ScalexpertPlugin\Helper\API\Client $apiClient */
                $apiClient = $this->get('scalexpert.api.client');
                $financingSubscription = $apiClient->createFinancingSubscription($currentOrder, $solutionCode);

                if (!empty($financingSubscription['id'])) {
                    $entityManager = $this->get('doctrine.orm.entity_manager');
                    $orderFinancing = new ScalexpertOrderFinancing();
                    $orderFinancing->setIdOrder((int)$currentOrder->id);
                    $orderFinancing->setIdSubscription((string)$financingSubscription['id']);
                    $entityManager->persist($orderFinancing);
                    $entityManager->flush();

                    Tools::redirect($financingSubscription['redirect']['value']);
                }

                $this->redirectWithError($currentOrder);
            } catch (\Exception $exception) {
                $this->redirectWithError($currentOrder);
            }
        }

        // Error during subscription creation
        $this->errors[] = $this->trans('An error occurred during financing subscription.', [], 'Modules.Scalexpertplugin.Shop');
        $this->redirectWithNotifications($this->context->link->getPageLink('cart', null, null, ['action' => 'show']));
    }

    protected function redirectWithError(Order $order): void
    {
        // Update order state
        $order->setCurrentState(Configuration::get('PS_OS_ERROR'));

        // Generate redirect url
        $redirectURL = \Context::getContext()->link->getModuleLink(
            'scalexpertplugin',
            'confirmation',
            [
                'cart_id' => $order->id_cart,
                'order_id' => $order->id,
                'k' => $order->secure_key,
            ]
        );
        \Tools::redirect($redirectURL);
    }

    protected function checkBeforeValidation(
        $cart,
        $customer
    ): bool
    {
        if (
            !($this->module instanceof ScalexpertPlugin)
            || !$this->module->active
        ) {
            Tools::redirect('index.php?controller=order&step=1');
            return false;
        }

        if (
            $cart->id_customer == 0
            || $cart->id_address_delivery == 0
            || $cart->id_address_invoice == 0
        ) {
            Tools::redirect('index.php?controller=order&step=1');
            return false;
        }

        // Check that this payment option is still available in case the customer changed his address
        // just before the end of the checkout process
        $authorized = false;
        foreach (Module::getPaymentModules() as $module) {
            if ('scalexpertplugin' === $module['name']) {
                $authorized = true;
                break;
            }
        }

        if (!$authorized) {
            $this->errors[] = $this->trans('This payment method is not available.', [], 'Modules.Scalexpertplugin.Shop');
            $this->redirectWithNotifications(
                $this->context->link->getPageLink('cart', null, null, ['action' => 'show'])
            );
        }

        if (!Validate::isLoadedObject($customer)) {
            Tools::redirect('index.php?controller=order&step=1');
            return false;
        }

        $address = new Address((int)$cart->id_address_delivery);
        if (Validate::isLoadedObject($address)) {
            try {
                $this->parsePhone($address->phone_mobile, $address->id_country);
                $this->parsePhone($address->phone, $address->id_country);
                $address->save();
                $isValid = true;
            } catch (\Exception $e) {
                $isValid = false;
            }

            if (!$isValid) {
                $this->errors[] = $this->trans('Please provide a valid phone number to select this payment method', [], 'Modules.Scalexpertplugin.Shop');
                $this->redirectWithNotifications($this->context->link->getPageLink('order'));
            }
        }

        return true;
    }

    /**
     * @throws Exception
     */
    protected function parsePhone(&$phone, $idCountry): void
    {
        if (empty($phone)) {
            return;
        }

        $isoCode = Country::getIsoById($idCountry);
        $phoneNumberUtil = \libphonenumber\PhoneNumberUtil::getInstance();
        $phoneNumberObject = $phoneNumberUtil->parse($phone, $isoCode);
        $isValid = $phoneNumberUtil->isValidNumber($phoneNumberObject);
        if (!$isValid) {
            throw new \Exception('Phone is not valid');
        }

        $formattedPhone = $phoneNumberUtil->format($phoneNumberObject, \libphonenumber\PhoneNumberFormat::E164);
        $phone = $formattedPhone;
    }
}
