<?php
/**
 * Copyright © Scalexpert.
 * This file is part of Scalexpert plugin for PrestaShop.
 *
 * @author    Société Générale
 * @copyright Scalexpert
 */

use DATASOLUTION\Module\Scalexpert\Api\Financing;
use PrestaShop\PrestaShop\Core\Payment\PaymentOption;

require_once __DIR__ . '/vendor/autoload.php';

if (!defined('_PS_VERSION_')) {
    exit;
}

class ScalexpertPlugin extends PaymentModule
{
    const MODULE_ADMIN_PARENT_CONTROLLER = 'AdminScalexpert';
    const MODULE_ADMIN_CONTROLLERS = [
        'AdminScalexpertAdministration',
        'AdminScalexpertCustomize'
    ];
    const TYPES = [
        0 => 'financials',
        1 => 'insurance',
    ];

    const ORDER_STATES = [
        0 => [
            'configuration' => 'SCALEXPERT_ORDER_STATE_ACCEPTED',
            'name' => 'Financing request accepted',
            'color' => '#228B22',
            'logable' => true,
            'paid' => true,
            'invoice' => false,
            'shipped' => false,
            'delivery' => false,
            'pdf_delivery' => false,
            'pdf_invoice' => false,
            'send_email' => false,
            'hidden' => false,
            'unremovable' => false,
            'template' => '',
            'deleted' => false,
        ],
        1 => [
            'configuration' => 'SCALEXPERT_ORDER_STATE_INITIALIZED',
            'name' => 'Financing request initialized',
            'color' => '#ADFF2F',
            'logable' => true,
            'paid' => true,
            'invoice' => false,
            'shipped' => false,
            'delivery' => false,
            'pdf_delivery' => false,
            'pdf_invoice' => false,
            'send_email' => false,
            'hidden' => false,
            'unremovable' => false,
            'template' => '',
            'deleted' => false,
        ],
        2 => [
            'configuration' => 'SCALEXPERT_ORDER_STATE_REQUESTED',
            'name' => 'Financing request requested',
            'color' => '#66CDAA',
            'logable' => true,
            'paid' => true,
            'invoice' => false,
            'shipped' => false,
            'delivery' => false,
            'pdf_delivery' => false,
            'pdf_invoice' => false,
            'send_email' => false,
            'hidden' => false,
            'unremovable' => false,
            'template' => '',
            'deleted' => false,
        ],
        3 => [
            'configuration' => 'SCALEXPERT_ORDER_STATE_PRE_ACCEPTED',
            'name' => 'Financing request pre accepted',
            'color' => '#00FF7F',
            'logable' => true,
            'paid' => true,
            'invoice' => false,
            'shipped' => false,
            'delivery' => false,
            'pdf_delivery' => false,
            'pdf_invoice' => false,
            'send_email' => false,
            'hidden' => false,
            'unremovable' => false,
            'template' => '',
            'deleted' => false,
        ],
        4 => [
            'configuration' => 'SCALEXPERT_ORDER_STATE_REJECTED',
            'name' => 'Financing request rejected',
            'color' => '#8B0000',
            'logable' => true,
            'paid' => true,
            'invoice' => false,
            'shipped' => false,
            'delivery' => false,
            'pdf_delivery' => false,
            'pdf_invoice' => false,
            'send_email' => false,
            'hidden' => false,
            'unremovable' => false,
            'template' => '',
            'deleted' => false,
        ],
        5 => [
            'configuration' => 'SCALEXPERT_ORDER_STATE_ABORTED',
            'name' => 'Financing request aborted',
            'color' => '#FF0000',
            'logable' => false,
            'paid' => false,
            'invoice' => false,
            'shipped' => false,
            'delivery' => false,
            'pdf_delivery' => false,
            'pdf_invoice' => false,
            'send_email' => false,
            'hidden' => false,
            'unremovable' => false,
            'template' => '',
            'deleted' => false,
        ],
        5 => [
            'configuration' => 'SCALEXPERT_ORDER_STATE_CANCELLED',
            'name' => 'Financing request cancelled',
            'color' => '#FF0000',
            'logable' => false,
            'paid' => false,
            'invoice' => false,
            'shipped' => false,
            'delivery' => false,
            'pdf_delivery' => false,
            'pdf_invoice' => false,
            'send_email' => false,
            'hidden' => false,
            'unremovable' => false,
            'template' => '',
            'deleted' => false,
        ],
    ];
    const API_FINANCING_STATES = [
        0 => 'ACCEPTED',
        1 => 'INITIALIZED',
        2 => 'REQUESTED',
        3 => 'PRE_ACCEPTED',
        4 => 'REJECTED',
        5 => 'ABORTED',
        6 => 'CANCELLED',
    ];

    const MATCHING_STATES = [
        self::API_FINANCING_STATES[0] => self::ORDER_STATES[0]['configuration'],
        self::API_FINANCING_STATES[1] => self::ORDER_STATES[1]['configuration'],
        self::API_FINANCING_STATES[2] => self::ORDER_STATES[2]['configuration'],
        self::API_FINANCING_STATES[3] => self::ORDER_STATES[3]['configuration'],
        self::API_FINANCING_STATES[4] => self::ORDER_STATES[4]['configuration'],
        self::API_FINANCING_STATES[5] => self::ORDER_STATES[5]['configuration'],
        self::API_FINANCING_STATES[6] => self::ORDER_STATES[6]['configuration'],
    ];


    public function __construct()
    {
        $this->name = 'scalexpertplugin';
        $this->tab = 'payments_gateways';
        $this->version = '1.0.0';
        $this->author = 'Société générale';
        $this->need_instance = 0;

        /**
         * Set $this->bootstrap to true if your module is compliant with bootstrap (PrestaShop 1.6)
         */
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Scalexpert');
        $this->description = $this->l('Module de demande de financements par Société Générale');

        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => '1.7.99.99');
    }

    public function install()
    {
        if (extension_loaded('curl') == false) {
            $this->_errors[] = $this->l('You have to enable the cURL extension on your server to install this module');
            return false;
        }

        Configuration::updateValue('SCALEXPERT_ENVIRONMENT', 'test');

        return parent::install() &&
            $this->installTabs() &&
            $this->createFinancingOrderStates() &&
            $this->registerHook('displayBackOfficeHeader') &&
            $this->registerHook('header') &&
            $this->registerHook('displayProductButtons') &&
            //$this->registerHook('displayAdminProductsExtra') &&
            $this->registerHook('hookDisplayAdminOrder') &&
            $this->registerHook('displayAdminOrderSide') &&
            $this->registerHook('payment') &&
            $this->registerHook('paymentReturn') &&
            $this->registerHook('paymentOptions');
    }

    public function installTabs()
    {
        // We create the Parent Tab
        if (!$parentTabId = Tab::getIdFromClassName(static::MODULE_ADMIN_PARENT_CONTROLLER)) {
            $parentTab = new Tab();
            $parentTab->class_name = static::MODULE_ADMIN_PARENT_CONTROLLER;
            $parentTab->active = true;
            $parentTab->name = array_fill_keys(
                Language::getIDs(false),
                $this->displayName
            );
            $parentTab->id_parent = 0;
            $parentTab->module = $this->name;
            $parentTab->add();
            $parentTabId = $parentTab->id;
        }

        $langName = [
            'AdminScalexpertAdministration' => [
                'en' => 'Configure',
                'fr' => 'Administrer',
                'de' => 'Configure',
            ],
            'AdminScalexpertCustomize'  => [
                'en' => 'Personalize',
                'fr' => 'Personnaliser',
                'de' => 'Personalize',
            ]
        ];

        // We create the other Tabs
        foreach (static::MODULE_ADMIN_CONTROLLERS as $controllerName) {
            if (Tab::getIdFromClassName($controllerName)) {
                continue;
            }

            $tab = new Tab();
            $tab->class_name = $controllerName;
            $tab->active = true;
            $tab->name = [];
            foreach (Language::getLanguages(false) as $lang) {
                $tab->name[$lang['id_lang']] = $langName[$controllerName][strtolower($lang['iso_code'])];
            }
            $tab->id_parent = (int)$parentTabId;
            $tab->module = $this->name;
            $tab->add();
        }

        return true;
    }

    public function createFinancingOrderStates()
    {
        $createFinancingOrderStates = true;
        $langs = \Language::getLanguages();

        if (!empty(self::ORDER_STATES)) {
            foreach (self::ORDER_STATES as $orderStateToAdd) {
                $names = [];
                foreach ($langs as $lang) {
                    $names[$lang['id_lang']] = $orderStateToAdd['name'];
                }

                $idOrderState = Configuration::get($orderStateToAdd['configuration']);
                if ($idOrderState) {
                    $orderState = new OrderState((int) $idOrderState);
                } else {
                    $orderState = new OrderState();
                }

                $orderState->module_name = $this->name;
                $orderState->name = $names;
                $orderState->color = $orderStateToAdd['color'];
                $orderState->logable = $orderStateToAdd['logable'];
                $orderState->paid = $orderStateToAdd['paid'];
                $orderState->invoice = $orderStateToAdd['invoice'];
                $orderState->shipped = $orderStateToAdd['shipped'];
                $orderState->delivery = $orderStateToAdd['delivery'];
                $orderState->pdf_delivery = $orderStateToAdd['pdf_delivery'];
                $orderState->pdf_invoice = $orderStateToAdd['pdf_invoice'];
                $orderState->send_email = $orderStateToAdd['send_email'];
                $orderState->hidden = $orderStateToAdd['hidden'];
                $orderState->unremovable = $orderStateToAdd['unremovable'];
                $orderState->template = $this->name;
                $orderState->deleted = $orderStateToAdd['deleted'];

                $orderStateAdd = (bool) $orderState->save();
                $createFinancingOrderStates &= $orderStateAdd;

                if ($orderStateAdd) {
                    Configuration::updateValue($orderStateToAdd['configuration'], $orderState->id);
                }
            }
        }

        return $createFinancingOrderStates;
    }

    public function uninstall()
    {
        return parent::uninstall()
            && $this->uninstallTabs()
            && $this->uninstallFinancingOrderStates()
            && $this->uninstallConfigVars();
    }

    public function uninstallFinancingOrderStates()
    {
        if (!empty(self::ORDER_STATES)) {
            foreach (self::ORDER_STATES as $orderStateToAdd) {
                $idOrderState = Configuration::get($orderStateToAdd['configuration']);
                if ($idOrderState) {
                    $orderState = new OrderState((int) $idOrderState);
                    $orderState->delete();
                }
            }
        }

        return true;
    }

    public function uninstallTabs()
    {
        foreach (static::MODULE_ADMIN_CONTROLLERS as $controllerName) {
            $idTab = (int)Tab::getIdFromClassName($controllerName);
            $tab = new Tab($idTab);
            if (Validate::isLoadedObject($tab)) {
                $tab->delete();
            }
        }

        $idTab = (int)Tab::getIdFromClassName(static::MODULE_ADMIN_PARENT_CONTROLLER);
        $tab = new Tab($idTab);
        if (Validate::isLoadedObject($tab)) {
            $tab->delete();
        }

        return true;
    }

    public function uninstallConfigVars()
    {
        $vars = [
            'SCALEXPERT_ENVIRONMENT',
            'SCALEXPERT_API_TEST_IDENTIFIER',
            'SCALEXPERT_API_TEST_KEY',
            'SCALEXPERT_API_PRODUCTION_IDENTIFIER',
            'SCALEXPERT_API_PRODUCTION_KEY',
            'SCALEXPERT_FINANCING_SOLUTIONS',
            'SCALEXPERT_INSURANCE_SOLUTIONS',
            'SCALEXPERT_GROUP_FINANCING_SOLUTIONS',
            'SCALEXPERT_CUSTOMIZE_PRODUCT',
            'SCALEXPERT_ORDER_STATE_ACCEPTED',
            'SCALEXPERT_ORDER_STATE_ACCEPTED',
            'SCALEXPERT_ORDER_STATE_ACCEPTED',
            self::ORDER_STATES[0]['configuration'],
            self::ORDER_STATES[1]['configuration'],
            self::ORDER_STATES[2]['configuration'],
            self::ORDER_STATES[3]['configuration'],
            self::ORDER_STATES[4]['configuration'],
            self::ORDER_STATES[5]['configuration'],
        ];

        foreach ($vars as $name) {
            Configuration::deleteByName($name);
        }

        return true;
    }

    public function hookHeader()
    {
        if (!empty($this->context->controller->php_self)) {
            switch ($this->context->controller->php_self) {
                case 'product' :
                    if (version_compare(_PS_VERSION_, '1.7', '>=')) {
                        Media::addJsDef([
                            'scalexpertpluginProductId' => (int)Tools::getValue('id_product'),
                            'scalexpertpluginFrontUrl' => $this->context->link->getModuleLink('scalexpertplugin', 'display'),
                        ]);
                        $this->context->controller->registerStylesheet(
                            'frontProductButtonsCSS',
                            $this->_path . 'views/css/ps17/frontProductButtons.css'
                        );

                        $this->context->controller->registerJavascript(
                            'frontProductButtonsJS',
                            $this->_path . 'views/js/ps17/frontProductButtons.js'
                        );
                        $this->context->controller->registerJavascript(
                            'frontProductButtonsInsuranceJS',
                            $this->_path . 'views/js/ps17/frontProductButtons-insurance.js'
                        );
                    } else {
                        $this->context->controller->addJS($this->_path . 'views/js/ps16/frontProductButtons.js');
                        $this->context->controller->addCSS($this->_path . 'views/css/ps16/frontProductButtons.css');
                    }
                    break;
                case 'cart' :
                    break;
                case 'order' :
                    if (version_compare(_PS_VERSION_, '1.7', '>=')) {
                        Media::addJsDef([
                            'scalexpertpluginFrontUrl' => $this->context->link->getModuleLink('scalexpertplugin', 'display'),
                        ]);
                        $this->context->controller->registerStylesheet(
                            'frontPaymentOptionsCSS',
                            $this->_path . 'views/css/ps17/frontPaymentOptions.css'
                        );

                        $this->context->controller->registerJavascript(
                            'frontPaymentOptionsJS',
                            $this->_path . 'views/js/ps17/frontPaymentOptions.js'
                        );
                    }
                    else {
                        $this->context->controller->addCSS($this->_path . 'views/css/ps16/frontPayment.css');
                    }
                    break;
            }

            if (version_compare(_PS_VERSION_, '1.7', '>=')) {
                $this->context->controller->registerStylesheet(
                    'frontContentModalCSS',
                    $this->_path . 'views/css/ps17/frontContentModal.css'
                );
            }
            else {
                $this->context->controller->addCSS($this->_path . 'views/css/ps16/frontGlobal.css');
            }
        }
    }

    public function hookDisplayProductButtons($params)
    {

        if (version_compare(_PS_VERSION_, '1.7', '>=')) {
            return $this->display(__FILE__, 'views/templates/hook/ps17/hookDisplayProductButtons.tpl');

        }

        $this->context->smarty->assign([
            'scalexpertpluginProductId' => (int)Tools::getValue('id_product'),
            'scalexpertpluginFrontUrl' => $this->context->link->getModuleLink('scalexpertplugin', 'display')
        ]);
        return $this->display(__FILE__, 'views/templates/hook/ps16/hookDisplayProductButtons.tpl');

    }

    public function hookDisplayShoppingCartFooter($params)
    {

        if (version_compare(_PS_VERSION_, '1.7', '>=')) {
            return $this->display(__FILE__, 'views/templates/hook/ps17/hookDisplayShoppingCartFooter.tpl');

        }

        $this->context->smarty->assign([
            'scalexpertpluginFrontUrl' => $this->context->link->getModuleLink('scalexpertplugin', 'display')
        ]);
        return $this->display(__FILE__, 'views/templates/hook/ps16/hookDisplayShoppingCartFooter.tpl');

    }

    /**
     * Add the CSS & JavaScript files you want to be loaded in the BO.
     */
    public function hookDisplayBackOfficeHeader()
    {
        $this->context->controller->addCSS($this->_path . 'views/css/back.css');

        if (
            !empty($this->context->controller->module)
            && $this->context->controller->module->name == $this->name
        ) {
            $this->context->controller->addJquery(); // Force jQuery first
            $this->context->controller->addJS($this->_path . 'views/js/back.js');
        }
    }

    /**
     * This method is used to render the payment button,
     * Take care if the button should be displayed or not.
     */
    public function hookPayment($params)
    {
        /** @var Cart $oCart */
        $oCart = $params['cart'];

        $customizeProduct = DATASOLUTION\Module\Scalexpert\Helper\FinancingEligibility::getEligibleSolutionByCart($oCart);
        if (empty($customizeProduct)) {
            return false;
        }

        $eligibleSolutions = DATASOLUTION\Module\Scalexpert\Api\Financing::getEligibleSolutionsForFront(
            $oCart->getOrderTotal(),
            strtoupper($this->context->language->iso_code)
        );

        foreach ($eligibleSolutions as $solutionCode => $eligibleSolution) {
            if (isset($customizeProduct[$eligibleSolution['solutionCode']])) {
                if (!empty($customizeProduct[$eligibleSolution['solutionCode']]['title_payment'])) {
                    $eligibleSolutions[$solutionCode]['communicationKit']['visualTitle'] = $customizeProduct[$eligibleSolution['solutionCode']]['title_payment'];
                }

                $eligibleSolutions[$solutionCode]['communicationKit']['displayLogo'] = $customizeProduct[$eligibleSolution['solutionCode']]['logo_payment'];
            }
        }

        $this->smarty->assign([
            'module_dir' => $this->_path,
            'eligibleSolutions' => $eligibleSolutions,
        ]);

        return $this->display(__FILE__, 'views/templates/hook/payment.tpl');
    }

    /**
     * Return payment options available for PS 1.7+
     *
     * @param array Hook parameters
     *
     * @return array|null
     */
    public function hookPaymentOptions($params)
    {
        if (!$this->active) {
            return;
        }

        if (!$this->checkCurrency($params['cart'])) {
            return;
        }

        /** @var Cart $oCart */
        $oCart = $params['cart'];

        $customizeProduct = DATASOLUTION\Module\Scalexpert\Helper\FinancingEligibility::getEligibleSolutionByCart($oCart);
        if (empty($customizeProduct)) {
            return;
        }

        $buyerCountry = strtoupper($this->context->language->iso_code);
        if ($oCart->id_address_invoice) {
            $oAddress = new Address($oCart->id_address_invoice);
            if (Validate::isLoadedObject($oAddress)) {
                $oCountry = new Country($oAddress->id_country);
                if (Validate::isLoadedObject($oCountry)) {
                    $buyerCountry = strtoupper($oCountry->iso_code);
                }
            }
        }

        $eligibleSolutions = DATASOLUTION\Module\Scalexpert\Api\Financing::getEligibleSolutionsForFront(
            $oCart->getOrderTotal(),
            $buyerCountry
        );

        if (!$eligibleSolutions) {
            return;
        }

//        $redirectControllerLink = $this->context->link->getModuleLink($this->name, 'redirect', [], true);
        $redirectControllerLink = $this->context->link->getModuleLink($this->name, 'validation', [], true);
        $regroupPayments = (int)Configuration::get('SCALEXPERT_GROUP_FINANCING_SOLUTIONS');
        $availableOptions = [];

        $this->smarty->assign(
            $this->name . '_global',
            [
                'nameModule' => $this->name
            ]
        );

        foreach ($eligibleSolutions as &$eligibleSolutionTemp) {
            if(!empty($eligibleSolutionTemp['communicationKit'])) {
                $eligibleSolutionTemp = $eligibleSolutionTemp['communicationKit'];

                if (isset($customizeProduct[$eligibleSolutionTemp['solutionCode']])) {
                    if (!empty($customizeProduct[$eligibleSolutionTemp['solutionCode']]['title_payment'])) {
                        $eligibleSolutionTemp['visualTitle'] = $customizeProduct[$eligibleSolutionTemp['solutionCode']]['title_payment'];
                    }
                    $eligibleSolutionTemp['useLogo'] = 0;
                    if (!empty($customizeProduct[$eligibleSolutionTemp['solutionCode']]['logo_payment'])) {
                        $eligibleSolutionTemp['useLogo'] = 1;
                    }
                }
            }
        }

        if (!empty($regroupPayments)) {

            $this->smarty->assign([
                'availableFinancialSolutions' => $eligibleSolutions,
                'redirectControllerLink' => $redirectControllerLink,
            ]);

            $availableOption = new PaymentOption();
            $availableOption->setModuleName($this->name)
                ->setCallToActionText(
                    $this->l('Pay your purchase by installments')
                )
                ->setAction($redirectControllerLink)
                ->setAdditionalInformation($this->fetch('module:' . $this->name . '/views/templates/hook/ps17/regroupedPayments.tpl'))
                ->setBinary(true);

            $availableOptions[] = $availableOption;

        } else {

            foreach ($eligibleSolutions as $k => $eligibleSolution) {
                $this->smarty->assign([
                    'availableSolution' => $eligibleSolution
                ]);

                $availableOption = new PaymentOption();
                $availableOption->setModuleName($this->name)
                    ->setCallToActionText(strip_tags($eligibleSolution['visualTitle']))
//                    ->setAction($redirectControllerLink)
                    ->setAction(
                        $this->context->link->getModuleLink($this->name, 'validation', [
                            'solutionCode' => $k
                        ], true)
                    )
                    ->setAdditionalInformation($this->fetch('module:' . $this->name . '/views/templates/hook/ps17/paymentAdditionalInformation.tpl'));

                if ($eligibleSolution['useLogo']) {
                    $availableOption->setLogo($eligibleSolution['visualLogo']);
                }

                $availableOptions[] = $availableOption;
            }
        }

        return $availableOptions;
    }

    /**
     * This hook is used to display the order confirmation page.
     */
    public function hookPaymentReturn($params)
    {
        if (false === $this->active) {
            return '';
        }

        if (version_compare(_PS_VERSION_, '1.7', '>=')) {
            $order = $params['order'];
            $total = Tools::displayPrice($order->total_paid, $this->context->currency);
        } else {
            $order = $params['objOrder'];
            $total = Tools::displayPrice($params['total_to_pay'], $params['currencyObj']);
        }
        if ($order->getCurrentOrderState()->id != Configuration::get('PS_OS_ERROR')) {
            $this->smarty->assign('status', 'ok');
        }

        $payment = $order->getOrderPaymentCollection()->getFirst();
        $subscriptionInfo = DATASOLUTION\Module\Scalexpert\Api\Financing::getSubscriptionInfo($payment->transaction_id);

        $status = $this->l('Unknown state');
        if (isset($subscriptionInfo['consolidatedStatus'])) {
            $orderState = $this->getOrderSateByApiState($subscriptionInfo['consolidatedStatus']);
            if ($orderState) {
                $order->setCurrentState((int) $orderState->id);
                $status = $this->getFinancialStateName($subscriptionInfo['consolidatedStatus']);
            }
        }

        $this->smarty->assign(array(
            'id_order' => $order->id,
            'reference' => $order->reference,
            'id_subscription' => $payment->transaction_id,
            'subscription_status' => $status,
            'params' => $params,
            'total' => $total,
            'shop_name' => $this->context->shop->name,
        ));

        if (version_compare(_PS_VERSION_, '1.7', '>=')) {
            return $this->display(__FILE__, 'views/templates/hook/ps17/confirmation.tpl');

        }

        return $this->display(__FILE__, 'views/templates/hook/ps16/confirmation.tpl');
    }

    public function hookDisplayAdminProductsExtra($params)
    {
        /*if (isset($params['id_product'])) {
            $idProduct = (int)$params['id_product'];
        } else {
            $idProduct = (int)Tools::getValue('id_product');
        }
        if (!$idProduct) {
            return;
        }

        $this->context->smarty->assign(array(
            'languages' => Language::getLanguages(false),
            'scalexpertplugin_model_values' => [
                1 => 'TEST 1',
                2 => 'TEST 2'
            ],
        ));

        return $this->display(__FILE__, '/hookDisplayAdminProductsExtra.tpl');*/
    }

    public function hookDisplayAdminOrder(array $params)
    {
        $financialSubscriptions = $this->getOrderSubscriptions($params);

        if (!empty($financialSubscriptions)) {
            $this->context->smarty->assign([
                'financialSubscriptions' => $financialSubscriptions,
            ]);
        }

        if (Tools::isSubmit('submitSubscriptionCancelRequest')) {
            $response = Financing::cancelFinancingSubscription(
                Tools::getValue('creditSubscriptionId'),
                Tools::getValue('buyerFinancedAmount'),
                Tools::getValue('id_order')
            );

            if ($response['hasError']) {
                $this->context->controller->errors[] = $response['error'];
            } else {
                $this->context->controller->success[] = $this->l('Your cancel request has been successfully sent.');
            }
        }

        $this->context->smarty->assign([
            'acceptedStatus' => self::API_FINANCING_STATES[0],
        ]);

        return $this->display(__FILE__, 'views/templates/admin/ps16/order-side.tpl');
    }

    public function hookDisplayAdminOrderSide(array $params)
    {
        $financialSubscriptions = $this->getOrderSubscriptions($params);

        if (!empty($financialSubscriptions)) {
            $this->context->smarty->assign([
                'financialSubscriptions' => $financialSubscriptions,
            ]);
        }

        if (Tools::isSubmit('submitSubscriptionCancelRequest')) {
            $response = Financing::cancelFinancingSubscription(
                Tools::getValue('creditSubscriptionId'),
                Tools::getValue('buyerFinancedAmount'),
                Tools::getValue('id_order')
            );

            if ($response['hasError']) {
                $this->get('session')->getFlashBag()->add('error', $response['error']);
            } else {
                $this->get('session')->getFlashBag()->add('success', 'Your cancel request has been successfully sent.');
            }

            Tools::redirectAdmin(
                $this->get('router')->generate('admin_orders_view', [
                    'orderId' => (int)Tools::getValue('id_order')
                ])
            );
        }

        $this->context->smarty->assign([
            'acceptedStatus' => self::API_FINANCING_STATES[0],
        ]);

        return $this->display(__FILE__, 'views/templates/admin/ps17/order-side.tpl');
    }

    private function getOrderSubscriptions(array $params)
    {
        if (empty($params['id_order'])) {
            return [];
        }

        $order = new Order($params['id_order']);
        if (!Validate::isLoadedObject($order)) {
            return [];
        }

        return Financing::getFinancingSubscriptionsByOrderReference($order->reference);

    }

    public function checkCurrency($cart)
    {
        $currency_order = new Currency($cart->id_currency);
        $currencies_module = $this->getCurrency($cart->id_currency);
        if (is_array($currencies_module)) {
            foreach ($currencies_module as $currency_module) {
                if ($currency_order->id == $currency_module['id_currency']) {
                    return true;
                }
            }
        }
        return false;
    }

    public function getSolutionDisplayName($solutionCode)
    {
        $data = [
            'SCFRSP-3XTS' => $this->l('Paiement en 3X (option sans frais)'),
            'SCFRSP-3XPS' => $this->l('Paiement en 3X (option avec frais)'),
            'SCFRSP-4XTS' => $this->l('Paiement en 4X (option sans frais)'),
            'SCFRSP-4XPS' => $this->l('Paiement en 4X (option avec frais)'),
            'SCDELT-DXTS' => $this->l('Crédit long (sans frais)'),
            'SCDELT-DXCO' => $this->l('Crédit long (avec commission)'),
            'SCFRLT-TXPS' => $this->l('Crédit long (sans frais)'),
            'SCFRLT-TXNO' => $this->l('Crédit long (avec commission)'),
            'CIFRWE-DXCO' => $this->l('Extention de garantie'),
        ];

        if (isset($data[$solutionCode])) {
            return $data[$solutionCode];
        }

        return null;
    }

    public function getSolutionFlag($solutionCode)
    {
        $data = [
            'SCFRSP-3XTS' => 'FR',
            'SCFRSP-3XPS' => 'FR',
            'SCFRSP-4XTS' => 'FR',
            'SCFRSP-4XPS' => 'FR',
            'SCDELT-DXTS' => 'DE',
            'SCDELT-DXCO' => 'DE',
            'SCFRLT-TXPS' => 'FR',
            'SCFRLT-TXNO' => 'FR',
            'CIFRWE-DXCO' => 'FR',
        ];

        if (isset($data[$solutionCode])) {
            return $data[$solutionCode];
        }

        return null;
    }

    public function getMissingSolution($existingSolution, $type)
    {
        $solutionGrouped = [
            'financials' => [
                [
                    'SCFRSP-3XTS',
                    'SCFRSP-3XPS'
                ],
                [
                    'SCFRSP-4XTS',
                    'SCFRSP-4XPS'
                ],
                [
                    'SCDELT-DXTS',
                ],
                [
                    'SCDELT-DXCO',
                ],
                [
                    'SCFRLT-TXPS',
                ],
                [
                    'SCFRLT-TXNO',
                ],
            ],
            'insurance' => [
                [
                    'CIFRWE-DXCO',
                ],
            ],
        ];

        if (!isset($solutionGrouped[$type])) {
            return [];
        }

        $missingSolution = [];
        foreach ($solutionGrouped[$type] as $solutionGroup) {
            $found = false;
            foreach ($solutionGroup as $solutionCode) {
                if (in_array($solutionCode, $existingSolution)) {
                    $found = true;
                }
            }
            if (!$found) {
                $missingSolution[] = $solutionGroup[0];
            }
        }

        return $missingSolution;
    }
    public function getNewContractUrlByLang(string $offerCode, string $isoCode)
    {
        $data = [
            self::TYPES[0] => [
                'en' => 'https://scalexpert.societegenerale.com/app/en/page/e-financing',
                'fr' => 'https://scalexpert.societegenerale.com/app/fr/page/e-financement',
            ],
            self::TYPES[1] => [
                'en' => 'https://scalexpert.societegenerale.com/app/en/page/warranty',
                'fr' => 'https://scalexpert.societegenerale.com/app/fr/page/garantie',
            ],
        ];

        return $data[strtolower($offerCode)][strtolower($isoCode)] ?? $data[self::TYPES[0]]['en'];
    }

    public function getOrderSateByApiState($consolidatedStatus)
    {
        $financingState = Configuration::get(self::MATCHING_STATES[$consolidatedStatus]);
        $orderState = new OrderState((int) $financingState, $this->context->language->id);
        return Validate::isLoadedObject($orderState) ? $orderState : null;
    }

    public function getFinancialStateName($idOrderState)
    {
        switch ($idOrderState) {
            case 'ACCEPTED':
                $status = $this->l('Financing request accepted');
                break;
            case 'INITIALIZED':
                $status = $this->l('Financing request in progress');
                break;
            case 'REQUESTED':
                $status = $this->l('Financing request requested');
                break;
            case 'PRE_ACCEPTED':
                $status = $this->l('Financing request pre-accepted');
                break;
            case 'REJECTED':
                $status = $this->l('Financing request rejected');
                break;
            case 'CANCELLED':
                $status = $this->l('Financing request cancelled');
                break;
            default:
                $status = $this->l('A technical error occurred during process, please retry.');
                break;
        }

        return $status;
    }
}
