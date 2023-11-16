<?php
/**
 * Copyright © Scalexpert.
 * This file is part of Scalexpert plugin for PrestaShop.
 *
 * @author    Société Générale
 * @copyright Scalexpert
 */

declare(strict_types=1);

if (!defined('_PS_VERSION_')) {
    exit;
}

// Needed for install process
require_once __DIR__ . '/vendor/autoload.php';

use PrestaShop\PrestaShop\Core\Payment\PaymentOption;
use ScalexpertPlugin\Controller\Admin\ConfigTabController;
use ScalexpertPlugin\Controller\Admin\DebugTabController;
use ScalexpertPlugin\Controller\Admin\DesignTabController;
use ScalexpertPlugin\Controller\Admin\KeysTabController;
use ScalexpertPlugin\Entity\ScalexpertCartInsurance;
use ScalexpertPlugin\Entity\ScalexpertProductCustomField;
use ScalexpertPlugin\Form\Configuration\DebugConfigurationFormDataConfiguration;
use ScalexpertPlugin\Form\Configuration\FinancingConfigurationFormDataConfiguration;
use ScalexpertPlugin\Form\Configuration\InsuranceConfigurationFormDataConfiguration;
use ScalexpertPlugin\Form\Configuration\KeysConfigurationFormDataConfiguration;
use ScalexpertPlugin\Form\Configuration\RegroupPaymentsConfigurationFormDataConfiguration;
use ScalexpertPlugin\Form\Customize\DesignCustomizeFormDataConfiguration;
use ScalexpertPlugin\Service\CartInsuranceProductsService;

class ScalexpertPlugin extends PaymentModule
{
    const CONFIGURATION_ORDER_STATE_FINANCING = 'SCALEXPERT_AWAITING_FINANCING';

    public function __construct()
    {
        $this->name = 'scalexpertplugin';
        $this->tab = 'payments_gateways';
        $this->version = '1.1.0';
        $this->author = 'Société générale';
        $this->need_instance = 0;

        $this->currencies = true;
        $this->currencies_mode = 'checkbox';

        $this->ps_versions_compliancy = [
            'min' => '8.0.0',
            'max' => _PS_VERSION_,
        ];
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->trans('Scalexpert', [], 'Modules.Scalexpertplugin.Admin');
        $this->description = $this->trans(
            'Description of Scalexpert module.',
            [],
            'Modules.Scalexpertplugin.Admin'
        );

        $this->confirmUninstall = $this->trans(
            'Are you sure you want to uninstall?',
            [],
            'Modules.Scalexpertplugin.Admin'
        );

        if (!count(Currency::checkPaymentCurrencies($this->id)) && $this->active) {
            $this->warning = $this->trans(
                'No currency has been set for this module.',
                []
                , 'Modules.Scalexpertplugin.Admin'
            );
        }
    }

    public function isUsingNewTranslationSystem()
    {
        return true;
    }

    public function install()
    {
        $installResult = parent::install()
            && $this->initDatabase()
            && $this->createInsuranceProductsCategory()
            && $this->createFinancingOrderState()
            && $this->manuallyInstallTab()
            && $this->registerHook('paymentOptions')
            && $this->registerHook('displayPaymentReturn')
            && $this->registerHook('actionFrontControllerSetMedia')
            && $this->registerHook('displayAdminProductsExtra')
            && $this->registerHook('displayAdminOrderSide')
            && $this->registerHook('actionCartSave')
            && $this->registerHook('actionOrderStatusPostUpdate')

            // Front
            && $this->registerHook('displayHeader')
            && $this->registerHook('displayProductAdditionalInfo')
            && $this->registerHook('displayShoppingCartFooter')
            && $this->registerHook('displayOrderConfirmation');

        if (version_compare(_PS_VERSION_, '8.0.0', '>')) {
            $installResult &= $this->registerHook('actionAfterUpdateProductFormHandler');
        } else {
            $installResult &= $this->registerHook('actionAdminProductsControllerSaveAfter');
        }

        return $installResult;
    }

    public function uninstall()
    {
        return parent::uninstall()
            && $this->deleteInsuranceProductsCategory()
            && $this->unInstallConfiVars()
            && $this->uninstallDatabase()
            && $this->uninstallFinancingOrderState();
    }

    public function initDatabase(): bool
    {
        try {
            Db::getInstance()->execute(
                'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'scalexpert_product_custom_field` (
                `id_product` INT(10) UNSIGNED NOT NULL,
                `model` VARCHAR(255) NULL,
                `characteristics` VARCHAR(255) NULL,
                PRIMARY KEY (`id_product`)
                ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;'
            );

            Db::getInstance()->execute(
                'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'scalexpert_order_financing` (
                `id_order` INT(10) UNSIGNED NOT NULL,
                `id_subscription` VARCHAR(255) NULL,
                PRIMARY KEY (`id_order`)
                ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;'
            );

            Db::getInstance()->execute(
                'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'scalexpert_cart_insurance` (
                `id_cart_insurance` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
                `id_product` INT(10) UNSIGNED NOT NULL,
                `id_product_attribute` INT(10) UNSIGNED,
                `quotations` VARCHAR(1024) NULL,
                `id_insurance_product` INT(10) UNSIGNED NULL,
                `id_cart` INT(10) UNSIGNED NOT NULL,
                `id_item` VARCHAR(255) NOT NULL,
                `id_insurance` VARCHAR(255) NOT NULL,
                `solution_code` VARCHAR(255) NOT NULL,
                `subscriptions` VARCHAR(1024) NULL,
                `subscriptions_processed` TINYINT(1) NOT NULL DEFAULT \'0\',
                PRIMARY KEY (`id_cart_insurance`)
                ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;'
            );
        } catch (\Exception $exception) {
            PrestaShopLogger::addLog($exception->getMessage(), 3);
            return false;
        }

        return true;
    }

    public function uninstallDatabase(): bool
    {
        $result = true;
        $sql[] = 'DROP TABLE IF EXISTS ' . _DB_PREFIX_ . 'scalexpert_product_custom_field';
        $sql[] = 'DROP TABLE IF EXISTS ' . _DB_PREFIX_ . 'scalexpert_order_financing';
        $sql[] = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'scalexpert_cart_insurance`';

        foreach ($sql as $query) {
            $result = $result && Db::getInstance()->execute($query);
        }

        return $result;
    }

    public function createInsuranceProductsCategory(): bool
    {
        $insuranceProductsCategory = new Category();

        $rootCategory = Category::getRootCategory();

        if (Validate::isLoadedObject($rootCategory)) {
            $insuranceProductsCategory->id_parent = $rootCategory->id;
        }

        $insuranceProductsCategoryData =  [
            'EN' => 'Insurance Products (do not delete)',
            'FR' => 'Produits d\'assurance (ne pas supprimer)',
            'DE' => 'Versicherungsprodukte (nicht löschen)',
        ];

        $insuranceProductsCategoryNames = [];
        $insuranceProductsCategoryLinkRewrites = [];

        foreach (Language::getLanguages() as $lang) {
            if (!empty($insuranceProductsCategoryData[strtoupper($lang['iso_code'])])) {
                $insuranceProductsCategoryNames[$lang['id_lang']] = $insuranceProductsCategoryData[strtoupper($lang['iso_code'])];
                $insuranceProductsCategoryLinkRewrites[$lang['id_lang']] = Tools::str2url($insuranceProductsCategoryData[strtoupper($lang['iso_code'])]);
            } else {
                $insuranceProductsCategoryNames[$lang['id_lang']] = $insuranceProductsCategoryData['EN'];
                $insuranceProductsCategoryLinkRewrites[$lang['id_lang']] = Tools::str2url($insuranceProductsCategoryData['EN']);
            }
        }

        $insuranceProductsCategory->name = $insuranceProductsCategoryNames;
        $insuranceProductsCategory->active = false;
        $insuranceProductsCategory->is_root_category = false;
        $insuranceProductsCategory->link_rewrite = $insuranceProductsCategoryLinkRewrites;

        $creationResult = $insuranceProductsCategory->save();

        if ($creationResult) {
            Configuration::updateValue(CartInsuranceProductsService::CONFIGURATION_INSURANCE_PRODUCTS_CATEGORY, $insuranceProductsCategory->id);
        }

        return !empty($creationResult);
    }

    public function createFinancingOrderState()
    {
        $languages = Language::getLanguages();
        $idOrderState = Configuration::get(self::CONFIGURATION_ORDER_STATE_FINANCING);

        if ($idOrderState) {
            $orderState = new OrderState((int) $idOrderState);
        } else {
            $orderState = new OrderState();
        }

        $orderState->module_name = $this->name;

        $orderStateLabel =  [
            'EN' => 'Awaiting financing',
            'FR' => 'En attente de financement',
        ];

        $names = [];
        foreach ($languages as $language) {
            if (isset($orderStateLabel[strtoupper($language['iso_code'])])) {
                $names[$language['id_lang']] = $orderStateLabel[strtoupper($language['iso_code'])];
            } else {
                $names[$language['id_lang']] = $orderStateLabel['EN'];
            }
        }

        $orderState->name = $names;
        $orderState->color = '#34209E';
        $orderState->logable = false;
        $orderState->paid = false;
        $orderState->invoice = false;
        $orderState->shipped = false;
        $orderState->delivery = false;
        $orderState->pdf_delivery = false;
        $orderState->pdf_invoice = false;
        $orderState->send_email = false;
        $orderState->hidden = false;
        $orderState->unremovable = true;
        $orderState->template = '';
        $orderState->deleted = false;

        $orderStateAdd = (bool) $orderState->add();

        if ($orderStateAdd) {
            Configuration::updateValue(self::CONFIGURATION_ORDER_STATE_FINANCING, $orderState->id);
        }

        return $orderStateAdd;
    }

    public function uninstallFinancingOrderState()
    {
        $idOrderState = Configuration::get(self::CONFIGURATION_ORDER_STATE_FINANCING);

        if ($idOrderState) {
            $orderState = new OrderState((int) $idOrderState);
            $orderState->delete();
        }

        return true;
    }

    public function deleteInsuranceProductsCategory(): bool
    {
        $insuranceProductsCategoryId = Configuration::get(CartInsuranceProductsService::CONFIGURATION_INSURANCE_PRODUCTS_CATEGORY);

        if (!empty($insuranceProductsCategoryId)) {
            $insuranceProductsCategory = new Category($insuranceProductsCategoryId);

            if (Validate::isLoadedObject($insuranceProductsCategory)) {
                return $insuranceProductsCategory->delete();
            }
        }

        return true;
    }

    public function manuallyInstallTab()
    {
        $data = [];

        // Menu
        $data[] = [
            'className' => 'AdminScalexpertPlugin',
            'routeName' => '',
            'name' => $this->getTabsTranslation('AdminScalexpertPlugin'),
            'parent' => 'IMPROVE',
            'position' => 20,
            'icon' => 'build',
        ];

        // Customize tab
        $data[] = [
            'className' => 'AdminScalexpertPluginParentCustomize',
            'routeName' => '',
            'name' => $this->getTabsTranslation('AdminScalexpertPluginParentCustomize'),
            'parent' => 'AdminScalexpertPlugin',
        ];

        // Design
        $data[] = [
            'className' => DesignTabController::TAB_CLASS_NAME,
            'routeName' => 'scalexpert_controller_tabs_admin_design',
            'name' => $this->getTabsTranslation(DesignTabController::TAB_CLASS_NAME),
            'parent' => 'AdminScalexpertPluginParentCustomize',
        ];

        // Parent Config
        $data[] = [
            'className' => 'AdminScalexpertPluginParentConfig',
            'routeName' => '',
            'name' => $this->getTabsTranslation('AdminScalexpertPluginParentConfig'),
            'parent' => 'AdminScalexpertPlugin',
        ];

        // Config
        $data[] = [
            'className' => ConfigTabController::TAB_CLASS_NAME,
            'routeName' => 'scalexpert_controller_tabs_admin_active',
            'name' => $this->getTabsTranslation(ConfigTabController::TAB_CLASS_NAME),
            'parent' => 'AdminScalexpertPluginParentConfig',
        ];

        // Debug
        $data[] = [
            'className' => DebugTabController::TAB_CLASS_NAME,
            'routeName' => 'scalexpert_controller_tabs_admin_debug',
            'name' => $this->getTabsTranslation(DebugTabController::TAB_CLASS_NAME),
            'parent' => 'AdminScalexpertPluginParentConfig',
        ];

        // Keys
        $data[] = [
            'className' => KeysTabController::TAB_CLASS_NAME,
            'routeName' => 'scalexpert_controller_tabs_admin_keys',
            'name' => $this->getTabsTranslation(KeysTabController::TAB_CLASS_NAME),
            'parent' => 'AdminScalexpertPluginParentConfig',
        ];

        foreach ($data as $tabData) {
            $tabId = (int)Tab::getIdFromClassName($tabData['className']);

            if (!$tabId) {
                $tabId = null;
            }

            $tab = new Tab($tabId);
            $tab->active = 1;
            $tab->class_name = $tabData['className'];
            $tab->route_name = $tabData['routeName'];
            $tab->name = $tabData['name'];
            $tab->icon = $tabData['icon'] ?? '';
            $tab->id_parent = (int) Tab::getIdFromClassName($tabData['parent']);
            $tab->module = $this->name;

            if (!$tab->save()) {
                return false;
            }
        }

        return true;
    }

    public function getTabsTranslation($tabClassName, $iso = null)
    {
        $data = [
            'AdminScalexpertPlugin' => [
                'EN' => 'Scalexpert',
                'FR' => 'Scalexpert',
                'DE' => 'Scalexpert',
            ],
            'AdminScalexpertPluginParentCustomize' => [
                'EN' => 'Customize',
                'FR' => 'Personnaliser',
                'DE' => 'Customize',
            ],
            DesignTabController::TAB_CLASS_NAME => [
                'EN' => 'Customize',
                'FR' => 'Personnaliser',
                'DE' => 'Customize',
            ],
            'AdminScalexpertPluginParentConfig' => [
                'EN' => 'Configure',
                'FR' => 'Administrer',
                'DE' => 'Configure',
            ],
            ConfigTabController::TAB_CLASS_NAME => [
                'EN' => 'Configure',
                'FR' => 'Administrer',
                'DE' => 'Configure',
            ],
            DebugTabController::TAB_CLASS_NAME => [
                'EN' => 'Debug mode',
                'FR' => 'Mode Débug',
                'DE' => 'Debug mode',
            ],
            KeysTabController::TAB_CLASS_NAME => [
                'EN' => 'Configure Keys',
                'FR' => 'Paramétrer les clés',
                'DE' => 'Configure Keys',
            ],
        ];

        if (!isset($data[$tabClassName])) {
            return '';
        }

        if ($iso) {
            if (isset($data[$tabClassName][$iso])) {
                return $data[$tabClassName][$iso];
            }
            return '';
        }

        $nameData = [];
        foreach (Language::getLanguages() as $lang) {
            if ($data[$tabClassName][strtoupper($lang['iso_code'])]) {
                $nameData[$lang['id_lang']] = $data[$tabClassName][strtoupper($lang['iso_code'])];
            } else {
                $nameData[$lang['id_lang']] = $data[$tabClassName]['EN'];
            }
        }

        return $nameData;
    }

    public function unInstallConfiVars()
    {
        $vars = [
            KeysConfigurationFormDataConfiguration::SCALEXPERT_KEYS_TYPE,
            KeysConfigurationFormDataConfiguration::SCALEXPERT_KEYS_ID_TEST,
            KeysConfigurationFormDataConfiguration::SCALEXPERT_KEYS_SECRET_TEST,
            KeysConfigurationFormDataConfiguration::SCALEXPERT_KEYS_ID_PROD,
            KeysConfigurationFormDataConfiguration::SCALEXPERT_KEYS_SECRET_PROD,
            RegroupPaymentsConfigurationFormDataConfiguration::CONFIGURATION_REGROUP_PAYMENTS,
            FinancingConfigurationFormDataConfiguration::CONFIGURATION_FINANCING,
            InsuranceConfigurationFormDataConfiguration::CONFIGURATION_INSURANCE,
            DebugConfigurationFormDataConfiguration::CONFIGURATION_DEBUG,
            DesignCustomizeFormDataConfiguration::CONFIGURATION_DESIGN,
            CartInsuranceProductsService::CONFIGURATION_INSURANCE_PRODUCTS_CATEGORY,
            self::CONFIGURATION_ORDER_STATE_FINANCING,
        ];

        foreach ($vars as $name) {
            Configuration::deleteByName($name);
        }

        return true;
    }

    //-----------------------------------------------------------------------------------------


    //-----------------------------------------------------------------------------------------

    public function hookPaymentOptions($params)
    {
        $availableSolutionsService = $this->get('scalexpert.service.available_solutions');
        $availableFinancialSolutions = $availableSolutionsService->getAvailableFinancialSolutions();
        $availableOptions = [];

        $regroupPayments = Configuration::get(RegroupPaymentsConfigurationFormDataConfiguration::CONFIGURATION_REGROUP_PAYMENTS);
        $designConfiguration = Configuration::get(DesignCustomizeFormDataConfiguration::CONFIGURATION_DESIGN);
        $designConfiguration = !empty($designConfiguration) ? json_decode($designConfiguration, true) : [];

        if (!empty($availableFinancialSolutions)) {
            foreach ($availableFinancialSolutions as &$availableFinancialSolution) {
                if (!empty($designConfiguration[$availableFinancialSolution['solutionCode']])) {
                    if (!empty($designConfiguration[$availableFinancialSolution['solutionCode']]['payment_title'])) {
                        $availableFinancialSolution['visualTitle'] =
                            $designConfiguration[$availableFinancialSolution['solutionCode']]['payment_title'];
                    }

                    if (empty($designConfiguration[$availableFinancialSolution['solutionCode']]['payment_display_logo'])) {
                        $availableFinancialSolution['visualLogo'] = null;
                    }
                }
            }

            if (!empty($regroupPayments)) {
                $redirectControllerLink = $this->context->link->getModuleLink($this->name, 'validation', [], true);

                $this->smarty->assign([
                    'availableFinancialSolutions' => $availableFinancialSolutions,
                    'redirectControllerLink' => $redirectControllerLink,
                ]);

                $availableOption = new PaymentOption();
                $availableOption->setModuleName($this->name)
                    ->setCallToActionText(
                        $this->trans(
                            'Pay your purchase by installments',
                            [],
                            'Modules.Scalexpertplugin.Admin'
                        )
                    )
                    ->setAction($redirectControllerLink)
                    ->setAdditionalInformation($this->fetch('module:' . $this->name . '/views/templates/hook/regrouped-payments.tpl'))
                    ->setBinary(true);

                $availableOptions[] = $availableOption;
            } else {
                unset($availableFinancialSolution);

                foreach ($availableFinancialSolutions as $availableFinancialSolution) {
                    $redirectControllerLink = $this->context->link->getModuleLink(
                        $this->name,
                        'validation',
                        ['solutionCode' => $availableFinancialSolution['solutionCode']],
                        true
                    );

                    $this->smarty->assign([
                        'availableSolution' => $availableFinancialSolution
                    ]);

                    $availableOption = new PaymentOption();
                    $availableOption->setModuleName($this->name)
                        ->setLogo($availableFinancialSolution['visualLogo'])
                        ->setCallToActionText(strip_tags($availableFinancialSolution['visualTitle']))
                        ->setAction($redirectControllerLink)
                        ->setAdditionalInformation($this->fetch('module:' . $this->name . '/views/templates/hook/additionalInformation.tpl'));

                    $availableOptions[] = $availableOption;
                }
            }
        }

        return $availableOptions;
    }

    public function hookDisplayPaymentReturn($params)
    {
        if (!$this->active) {
            return;
        }

        $order = (isset($params['objOrder'])) ? $params['objOrder'] : $params['order'];;

        if (!Validate::isLoadedObject($order)) {
            return;
        }

        $apiClient = $this->get('scalexpert.api.client');
        $financialSubscriptions = $apiClient->getFinancingSubscriptionsByOrderReference($order->reference);

        if (empty($financialSubscriptions)) {
            return;
        }

        foreach ($financialSubscriptions as &$financialSubscription) {
            if (isset($financialSubscription['consolidatedStatus'])) {

                $financialSubscription['consolidatedStatusError'] = false;
                if ('REJECTED' == $financialSubscription['consolidatedStatus']) {
                    $financialSubscription['consolidatedStatusError'] = true;
                }

                $financialSubscription['consolidatedStatus'] = $this->getFinancialStateName(
                    $financialSubscription['consolidatedStatus']
                );
            }

            if (!empty($financialSubscription['buyerFinancedAmount'])) {
                $financialSubscription['buyerFinancedAmount'] = Tools::displayPrice(
                    $financialSubscription['buyerFinancedAmount'],
                    (int) $order->id_currency
                );
            }
        }

        $this->smarty->assign([
            'reference' => $order->reference,
            'financialSubscriptions' => $financialSubscriptions,
        ]);

        return $this->fetch(
            'module:' . $this->name . '/views/templates/hook/order-confirmation-financing.tpl'
        );
    }

    public function hookActionCartSave(array $params)
    {
        if (!isset($this->context->cart)) {
            return;
        }

        if (!Tools::getIsset('id_product')) {
            return;
        }

        if (!empty($params['cart'])) {
            $cartInsuranceProductsService = $this->get('scalexpert.service.cart_insurance_products');
            $cartInsuranceProductsService->handleCartSave($params['cart']);
        }
    }

    public function hookActionFrontControllerSetMedia($params)
    {
        if (!empty($this->context->controller->php_self)) {
            switch ($this->context->controller->php_self) {
                case 'product' :
                    $getFinancialInsertsOnProductAjaxURL = $this->context->link->getModuleLink(
                        $this->name,
                        'ajax',
                        [
                            'ajax' => true,
                            'action' => 'GetFinancialInsertsOnProduct'
                        ]
                    );

                    $getInsuranceInsertsAjaxURL = $this->context->link->getModuleLink(
                        $this->name,
                        'ajax',
                        [
                            'ajax' => true,
                            'action' => 'GetInsuranceInserts'
                        ]
                    );

                    Media::addJsDef([
                        'getFinancialInsertsOnProductAjaxURL' => $getFinancialInsertsOnProductAjaxURL,
                        'getInsuranceInsertsAjaxURL' => $getInsuranceInsertsAjaxURL
                    ]);
                case 'cart' :
                    $getInsuranceInsertsAjaxURL = $this->context->link->getModuleLink(
                        $this->name,
                        'ajax',
                        [
                            'ajax' => true,
                            'action' => 'GetInsuranceInserts'
                        ]
                    );

                    $getFinancialInsertsOnCartAjaxURL = $this->context->link->getModuleLink(
                        $this->name,
                        'ajax',
                        [
                            'ajax' => true,
                            'action' => 'GetFinancialInsertsOnCart'
                        ]
                    );

                    Media::addJsDef([
                        'getFinancialInsertsOnCartAjaxURL' => $getFinancialInsertsOnCartAjaxURL,
                        'getInsuranceInsertsAjaxURL' => $getInsuranceInsertsAjaxURL
                    ]);
                    break;
                /*case 'order' :
                    dump($this->context->controller->php_self);
                    break;*/
            }
        }
    }

    public function hookDisplayAdminOrderSide(array $params): string
    {
        $templateData = [];

        if (empty($params['id_order'])) {
            return '';
        }

        $order = new Order($params['id_order']);
        if (!Validate::isLoadedObject($order)) {
            return '';
        }

        $apiClient = $this->get('scalexpert.api.client');
        $entityManager = $this->get('doctrine.orm.entity_manager');
        $cartInsuranceRepository = $entityManager->getRepository(ScalexpertCartInsurance::class);

        $cartInsurances = $cartInsuranceRepository->findBy(['idCart' => $order->id_cart]);

        $insuranceSubscriptionsByProduct = [];

        if (!empty($cartInsurances)) {
            foreach ($cartInsurances as $cartInsurance) {
                $insuredProduct = new Product($cartInsurance->getIdProduct(), false, $this->context->language->id);
                $insuranceProduct = new Product($cartInsurance->getIdInsuranceProduct(), false, $this->context->language->id);
                $subscriptions = $cartInsurance->getSubscriptions();
                $subscriptionsToAdd = [];

                if (!empty($subscriptions)) {
                    foreach ($subscriptions as $subscriptionId) {
                        $apiSubscription = $apiClient->getInsuranceSubscriptionBySubscriptionId($subscriptionId);

                        if (empty($apiSubscription)) {
                            continue;
                        }

                        $subscriptionsToAdd[] = [
                            'subscriptionId' => $subscriptionId ?? '',
                            'consolidatedStatus' => $apiSubscription['consolidatedStatus'] ?? '',
                            'duration' => $apiSubscription['duration'] ?? '',
                            'producerQuoteInsurancePrice' => $this->context->getCurrentLocale()->formatPrice(
                                    $apiSubscription['producerQuoteInsurancePrice'],
                                    Currency::getIsoCodeById($order->id_currency)
                                ) ?? '',
                        ];
                    }
                }

                $insuranceSubscriptionsByProduct[] = [
                    'productName' => $insuredProduct->name,
                    'insuranceName' => $insuranceProduct->name,
                    'subscriptions' => $subscriptionsToAdd,
                ];
            }
        }

        $templateData['insuranceSubscriptionsByProduct'] = $insuranceSubscriptionsByProduct;

        if (Tools::getIsset('scalexpert_cancel_financial_subscription')) {
            $financialSubscriptionId = Tools::getValue('scalexpert_cancel_financial_subscription_id');
            $financialSubscriptionAmount = (float)Tools::getValue('scalexpert_cancel_financial_subscription_amount');

            if (!empty($financialSubscriptionId) && !empty($financialSubscriptionAmount)) {

                $responseCancel = $apiClient->cancelFinancialSubscription(
                    $financialSubscriptionId,
                    $financialSubscriptionAmount
                );

                if (!$responseCancel) {
                    $this->get('session')->getFlashBag()->add('error',
                        $this->trans(
                            'An Error occurred during cancellation process.',
                            []
                            , 'Modules.Scalexpertplugin.Admin'
                        )
                    );
                } else {
                    if (isset($responseCancel['status'])
                        && 'ACCEPTED' == $responseCancel['status']) {
                        $this->get('session')->getFlashBag()->add('success',
                            $this->trans(
                                'Cancellation success.',
                                []
                                , 'Modules.Scalexpertplugin.Admin'
                            )
                        );
                    } else {
                        $this->get('session')->getFlashBag()->add('error',
                            $this->trans(
                                'An Error occurred during cancellation process.',
                                []
                                , 'Modules.Scalexpertplugin.Admin'
                            )
                        );
                    }
                }
            }

            Tools::redirectAdmin(
                $this->get('router')->generate('admin_orders_view', [
                    'orderId' => (int)Tools::getValue('id_order')
                ])
            );
        }

        $financialSubscriptions = $apiClient->getFinancingSubscriptionsByOrderReference($order->reference);

        if (!empty($financialSubscriptions)) {
            foreach ($financialSubscriptions as &$financialSubscription) {
                if (!empty($financialSubscription['buyerFinancedAmount'])) {
                    $financialSubscription['buyerFinancedAmountFloat'] = $financialSubscription['buyerFinancedAmount'];
                    $financialSubscription['buyerFinancedAmount'] = Tools::displayPrice(
                        $financialSubscription['buyerFinancedAmount'],
                        (int) $order->id_currency
                    );
                }
            }

            $templateData['financialSubscriptions'] = $financialSubscriptions;
        }

        return $this->get('twig')->render(
            '@Modules/' . $this->name . '/views/templates/admin/order-side.html.twig',
            $templateData
        );

    }

    public function hookDisplayAdminProductsExtra(array $params): string
    {
        $productId = $params['id_product'];

        $entityManager = $this->get('doctrine.orm.entity_manager');
        $repository = $entityManager->getRepository(ScalexpertProductCustomField::class);

        if (!empty($repository)) {
            $productCustomFields = $repository->find($productId);
        }

        return $this->get('twig')->render('@Modules/' . $this->name . '/views/templates/admin/product-extra-fields.html.twig', [
            'productCustomFields' => $productCustomFields ?? [],
        ]);
    }

    public function hookActionAdminProductsControllerSaveAfter($params)
    {
        $this->handleProductCustomFieldSave(Tools::getValue('id_product'));
    }

    public function hookActionAfterUpdateProductFormHandler($params)
    {
        $this->handleProductCustomFieldSave($params['id']);
    }

    public function handleProductCustomFieldSave($productId)
    {
        if (!empty($productId)) {
            $entityManager = $this->get('doctrine.orm.entity_manager');

            $productCustomField = new ScalexpertProductCustomField();
            $productCustomField->setIdProduct((int) $productId);

            $productModel = Tools::getValue('scalexpertplugin_model', '');
            $productCustomField->setModel($productModel);

            $productCharacteristics = Tools::getValue('scalexpertplugin_characteristics', '');
            $productCustomField->setCharacteristics($productCharacteristics);

            $entityManager->merge($productCustomField);
            $entityManager->flush();
        }
    }

    public function hookDisplayProductAdditionalInfo(array $params): string
    {
        return $this->fetch('module:' . $this->name . '/views/templates/hook/product-additional-info.tpl');
    }

    public function hookDisplayShoppingCartFooter(array $params): string
    {
        if (!empty(Tools::getValue('insurances'))) {
            $cartInsuranceProductsService = $this->get('scalexpert.service.cart_insurance_products');
            $cartInsuranceProductsService->handleInsuranceProductsFormSubmit($params['cart']);
            $cartInsuranceProductsService->handleCartQty($params['cart']);
            $cartInsuranceProductsService->handleQuotationsInCart($params['cart']);

            Tools::redirect($this->context->link->getPageLink('cart', null, null, ['action' => 'show']));
        }

        return $this->fetch('module:' . $this->name . '/views/templates/hook/shopping-cart-footer.tpl');
    }

    public function hookDisplayHeader($params)
    {
        if (!$this->active) {
            return;
        }

        if (!empty($this->context->controller->php_self)) {
            switch ($this->context->controller->php_self) {
                case 'product' :
                    $this->context->controller->registerStylesheet(
                        'frontProductAdditionalInfoCSS',
                        $this->_path . 'views/css/frontProductAdditionalInfo.css'
                    );

                    $this->context->controller->registerJavascript(
                        'frontProductAdditionalInfoJS',
                        $this->_path . 'views/js/frontProductAdditionalInfo.js'
                    );

                    $this->context->controller->registerJavascript(
                        'frontProductAdditionalInfoInsuranceJS',
                        $this->_path . 'views/js/frontProductAdditionalInfo-insurance.js'
                    );

                    $this->context->controller->registerStylesheet(
                        'frontProductAdditionalInfoInsuranceCSS',
                        $this->_path . 'views/css/frontProductAdditionalInfo-insurance.css'
                    );
                    break;
                case 'cart' :
                    $this->context->controller->registerJavascript(
                        'frontCartInsuranceJS',
                        $this->_path . 'views/js/frontCart-insurance.js'
                    );

                    $this->context->controller->registerStylesheet(
                        'frontProductAdditionalInfoInsuranceCSS',
                        $this->_path . 'views/css/frontProductAdditionalInfo-insurance.css'
                    );
                    break;
                case 'order' :
                    $this->context->controller->registerStylesheet(
                        'frontPaymentOptionsCSS',
                        $this->_path . 'views/css/frontPaymentOptions.css'
                    );

                    $this->context->controller->registerJavascript(
                        'frontPaymentOptionsJS',
                        $this->_path . 'views/js/frontPaymentOptions.js'
                    );
                    break;
            }
        }

        $this->context->controller->registerStylesheet(
            'frontContentModalCSS',
            $this->_path . 'views/css/frontContentModal.css'
        );
    }

    public function hookActionOrderStatusPostUpdate($params)
    {
        $insurancesSubscriptionsService = $this->get('scalexpert.service.insurances_subscriptions');
        $insurancesSubscriptionsService->createOrderInsurancesSubscriptions($params);

        return;
    }

    public function hookDisplayOrderConfirmation($params)
    {
        if (!empty($params['order']) && Validate::isLoadedObject($params['order'])) {
            $apiClient = $this->get('scalexpert.api.client');
            $entityManager = $this->get('doctrine.orm.entity_manager');
            $cartInsuranceRepository = $entityManager->getRepository(ScalexpertCartInsurance::class);

            $cartInsurances = $cartInsuranceRepository->findBy(['idCart' => $params['order']->id_cart]);

            $insuranceSubscriptionsByProduct = [];

            if (!empty($cartInsurances)) {
                foreach ($cartInsurances as $cartInsurance) {
                    $insuredProduct = new Product($cartInsurance->getIdProduct(), false, $this->context->language->id);
                    $insuranceProduct = new Product($cartInsurance->getIdInsuranceProduct(), false, $this->context->language->id);
                    $subscriptions = $cartInsurance->getSubscriptions();
                    $subscriptionsToAdd = [];

                    if (!empty($subscriptions)) {
                        foreach ($subscriptions as $subscriptionId) {
                            $apiSubscription = $apiClient->getInsuranceSubscriptionBySubscriptionId($subscriptionId);

                            if (empty($apiSubscription)) {
                                continue;
                            }

                            $subscriptionsToAdd[] = [
                                'subscriptionId' => $subscriptionId ?? '',
                                'consolidatedStatus' => $apiSubscription['consolidatedStatus'] ?? '',
                                'producerQuoteInsurancePrice' => $apiSubscription['producerQuoteInsurancePrice'] ?? '',
                            ];
                        }
                    }

                    $insuranceSubscriptionsByProduct[] = [
                        'productName' => $insuredProduct->name,
                        'insuranceName' => $insuranceProduct->name,
                        'subscriptions' => $subscriptionsToAdd,
                    ];
                }
            }

            $this->context->smarty->assign([
                'insuranceSubscriptionsByProduct' => $insuranceSubscriptionsByProduct,
            ]);
        }

        return $this->fetch('module:' . $this->name . '/views/templates/hook/order-confirmation-insurances.tpl');
    }

    public function getFinancialStateName($orderState)
    {
        switch ($orderState) {
            case 'ACCEPTED':
                $status = $this->trans('Financing request accepted', [], 'Modules.Scalexpertplugin.Shop');
                break;
            case 'INITIALIZED':
                $status = $this->trans('Financing request in progress', [], 'Modules.Scalexpertplugin.Shop');
                break;
            case 'REQUESTED':
                $status = $this->trans('Financing request requested', [], 'Modules.Scalexpertplugin.Shop');
                break;
            case 'PRE_ACCEPTED':
                $status = $this->trans('Financing request pre-accepted', [], 'Modules.Scalexpertplugin.Shop');
                break;
            case 'REJECTED':
                $status = $this->trans('Financing request rejected', [], 'Modules.Scalexpertplugin.Shop');
                break;
            case 'CANCELLED':
                $status = $this->trans('Financing request cancelled', [], 'Modules.Scalexpertplugin.Shop');
                break;
            default:
                $status = $this->trans('A technical error occurred during process, please retry.', [], 'Modules.Scalexpertplugin.Shop');
                break;
        }

        return $status;
    }
}