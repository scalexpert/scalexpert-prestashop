<?php
/**
 * Copyright © Scalexpert.
 * This file is part of Scalexpert plugin for PrestaShop. See COPYING.md for license details.
 *
 * @author    Scalexpert (https://scalexpert.societegenerale.com/)
 * @copyright Scalexpert
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
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
use ScalexpertPlugin\Controller\Admin\MappingTabController;
use ScalexpertPlugin\Entity\ScalexpertCartInsurance;
use ScalexpertPlugin\Entity\ScalexpertProductCustomField;
use ScalexpertPlugin\Form\Configuration\DebugConfigurationFormDataConfiguration;
use ScalexpertPlugin\Form\Configuration\FinancingConfigurationFormDataConfiguration;
use ScalexpertPlugin\Form\Configuration\InsuranceConfigurationFormDataConfiguration;
use ScalexpertPlugin\Form\Configuration\KeysConfigurationFormDataConfiguration;
use ScalexpertPlugin\Form\Configuration\MappingConfigurationFormDataConfiguration;
use ScalexpertPlugin\Form\Configuration\RegroupPaymentsConfigurationFormDataConfiguration;
use ScalexpertPlugin\Form\Customize\DesignCustomizeFormDataConfiguration;
use ScalexpertPlugin\Handler\InsuranceNameHandler;
use ScalexpertPlugin\Handler\SolutionNameHandler;
use ScalexpertPlugin\Helper\API\Client;
use ScalexpertPlugin\Helper\MappingGenerator;
use ScalexpertPlugin\Service\AvailableSolutionsService;
use ScalexpertPlugin\Service\CartInsuranceProductsService;
use ScalexpertPlugin\Service\InsurancesSubscriptionsService;
use ScalexpertPlugin\Service\SolutionSorterService;
use ScalexpertPlugin\Service\SubscriptionCanceler;
use ScalexpertPlugin\Service\SubscriptionDeliverer;

class ScalexpertPlugin extends PaymentModule
{
    const CONFIGURATION_ORDER_STATE_FINANCING = 'SCALEXPERT_AWAITING_FINANCING';

    public function __construct()
    {
        $this->name = 'scalexpertplugin';
        $this->tab = 'payments_gateways';
        $this->version = '1.5.0';
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
        return parent::install()
            && $this->initDatabase()
            && $this->createInsuranceProductsCategory()
            && $this->createFinancingOrderState()
            && $this->createMeta()
            && $this->createMapping()
            && $this->manuallyInstallTab()
            && $this->registerHooks()
            ;
    }

    private function registerHooks(): bool
    {
        $installResult = $this->registerHook('paymentOptions')
            && $this->registerHook('displayAdminOrderTop')
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

        return (bool)$installResult;
    }

    public function uninstall()
    {
        return parent::uninstall()
            && $this->deleteInsuranceProductsCategory()
            && $this->unInstallConfiVars()
            && $this->uninstallDatabase();
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

        $insuranceProductsCategoryData = [
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
            $orderState = new OrderState((int)$idOrderState);
        } else {
            $orderState = new OrderState();
        }

        $orderStateLabel = [
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

        $orderState->module_name = $this->name;
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

        try {
            $orderStateAdd = (bool)$orderState->save();
            if ($orderStateAdd) {
                Configuration::updateValue(self::CONFIGURATION_ORDER_STATE_FINANCING, $orderState->id);
            }
        } catch (PrestaShopException $e) {
            PrestaShopLogger::addLog('[SCALEXPERTPLUGIN] Error during install: ' . $e->getMessage());
        }

        return $orderStateAdd;
    }

    public function createMeta()
    {
        $page = 'module-' . $this->name . '-confirmation';
        $meta = Meta::getMetaByPage($page, Context::getContext()->language->id);
        if (!empty($meta)) {
            return true;
        }

        $langTitle = [
            'en' => 'Order confirmation',
            'fr' => 'Confirmation de commande',
            'de' => 'Order confirmation',
        ];
        $langUrl = [
            'en' => 'order-confirmation',
            'fr' => 'confirmation-de-commande',
            'de' => 'order-confirmation',
        ];
        $langs = \Language::getLanguages();
        $titles = [];
        $url_rewrites = [];
        foreach ($langs as $lang) {
            if (isset($langTitle[strtolower($lang['iso_code'])], $langUrl[strtolower($lang['iso_code'])])) {
                $titles[$lang['id_lang']] = $langTitle[strtolower($lang['iso_code'])];
                $url_rewrites[$lang['id_lang']] = $langUrl[strtolower($lang['iso_code'])];
            }
        }

        $meta = new Meta();
        $meta->page = $page;
        $meta->title = $titles;
        $meta->url_rewrite = $url_rewrites;

        return $meta->save();
    }

    public function createMapping(): bool
    {
        return MappingGenerator::generateDefaultMapping();
    }

    public function uninstallFinancingOrderState()
    {
        $idOrderState = Configuration::get(self::CONFIGURATION_ORDER_STATE_FINANCING);

        if ($idOrderState) {
            $orderState = new OrderState((int)$idOrderState);
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

        // Parent Config
        $data[] = [
            'className' => 'AdminScalexpertPluginParentConfig',
            'routeName' => '',
            'name' => $this->getTabsTranslation('AdminScalexpertPluginParentConfig'),
            'parent' => 'AdminScalexpertPlugin',
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

        // Order state mapping
        $data[] = [
            'className' => MappingTabController::TAB_CLASS_NAME,
            'routeName' => 'scalexpert_controller_tabs_admin_mapping',
            'name' => $this->getTabsTranslation(MappingTabController::TAB_CLASS_NAME),
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
            $tab->id_parent = (int)Tab::getIdFromClassName($tabData['parent']);
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
            MappingTabController::TAB_CLASS_NAME => [
                'EN' => 'Order state mapping',
                'FR' => 'Association des états de commande',
                'DE' => 'Order state mapping',
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
            if (isset($data[$tabClassName][strtoupper($lang['iso_code'])])) {
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
            MappingConfigurationFormDataConfiguration::CONFIGURATION_MAPPING,
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
        /* @var SolutionSorterService $solutionSorterService */
        /* @var AvailableSolutionsService $availableSolutionsService */
        $solutionSorterService = $this->get('scalexpert.service.solution_sorter');
        $availableSolutionsService = $this->get('scalexpert.service.available_solutions');

        $availableFinancialSolutions = $availableSolutionsService->getAvailableFinancialSolutions();
        if (empty($availableFinancialSolutions)) {
            return [];
        }

        $designConfiguration = json_decode(
            Configuration::get(DesignCustomizeFormDataConfiguration::CONFIGURATION_DESIGN, '{}'),
            true
        );

        [$availableSimulation, $groupedSolutionSimulations, $singleSolutionSimulations] = $this->getSingleAndGroupedSolutionSimulations(
            $availableSolutionsService,
            $solutionSorterService
        );

        $availableOptions = [];
        foreach ($availableFinancialSolutions as &$availableFinancialSolution) {
            if (!empty($designConfiguration[$availableFinancialSolution['solutionCode']])) {
                if (!empty($designConfiguration[$availableFinancialSolution['solutionCode']]['paymentTitle'])) {
                    $availableFinancialSolution['visualTitle'] =
                        $designConfiguration[$availableFinancialSolution['solutionCode']]['paymentTitle'];
                }

                if (
                    empty($designConfiguration[$availableFinancialSolution['solutionCode']]['paymentDisplayLogo'])
                ) {
                    $availableFinancialSolution['visualLogo'] = null;
                }

                $availableFinancialSolution['position'] = $designConfiguration[$availableFinancialSolution['solutionCode']]['position'];
                $availableFinancialSolution['simulation'] = $singleSolutionSimulations[$availableFinancialSolution['solutionCode']] ?? [];
                $availableFinancialSolution['simulationPopinData'] = $groupedSolutionSimulations[$availableFinancialSolution['solutionCode']] ?? [];
            }
        }

        $solutionSorterService->sortSolutionsByPosition($availableFinancialSolutions);

        $regroupPayments = Configuration::get(
            RegroupPaymentsConfigurationFormDataConfiguration::CONFIGURATION_REGROUP_PAYMENTS
        );
        if (!empty($regroupPayments)) {
            $redirectControllerLink = $this->context->link->getModuleLink($this->name, 'validation', [], true);

            $this->smarty->assign([
                'availableFinancialSolutions' => $availableFinancialSolutions,
                'financedAmountFormatted' => $availableSimulation['financedAmountFormatted'] ?? '',
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
                ->setAdditionalInformation(
                    $this->fetch('module:' . $this->name . '/views/templates/hook/regrouped-payments.tpl')
                )
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
                    'availableSolution' => $availableFinancialSolution,
                    'financedAmountFormatted' => $availableSimulation['financedAmountFormatted'] ?? '',
                ]);

                $availableOption = new PaymentOption();
                $availableOption->setModuleName($this->name)
                    ->setLogo($availableFinancialSolution['visualLogo'])
                    ->setCallToActionText(strip_tags($availableFinancialSolution['visualTitle']))
                    ->setAction($redirectControllerLink)
                    ->setAdditionalInformation(
                        $this->fetch('module:' . $this->name . '/views/templates/hook/additionalInformation.tpl')
                    );

                $availableOptions[] = $availableOption;
            }
        }

        return $availableOptions;
    }

    private function getSingleAndGroupedSolutionSimulations(
        AvailableSolutionsService $availableSolutionsService,
        SolutionSorterService $solutionSorterService
    ): array
    {
        $groupedSolutionSimulations = [];
        $singleSolutionSimulations = [];

        $availableSimulation = $availableSolutionsService->getSimulationForAvailableFinancialSolutions();
        if (
            !empty($availableSimulation)
            && isset($availableSimulation['solutionSimulations'])
        ) {
            // Group financing solutions by having fees or not
            foreach ($availableSimulation['solutionSimulations'] as $solutionSimulation) {
                foreach ($solutionSimulation['simulations'] as $simulation) {
                    $simulation['designConfiguration'] = $solutionSimulation['designConfiguration'];
                    $simulation['isLongFinancingSolution'] = $solutionSimulation['isLongFinancingSolution'];
                    $simulation['hasFeesOnFirstInstallment'] =
                        $solutionSimulation['hasFeesSolution']
                        && 0 < $simulation['feesAmount'];

                    $groupedSolutionSimulations[$solutionSimulation['solutionCode']][] = $simulation;
                    if (!isset($singleSolutionSimulations[$solutionSimulation['solutionCode']])) {
                        $singleSolutionSimulations[$solutionSimulation['solutionCode']] = $simulation;
                    }
                }
            }
        }

        if (!empty($groupedSolutionSimulations)) {
            foreach ($groupedSolutionSimulations as $groupedSolutionSimulation) {
                $solutionSorterService->sortSolutionsByDuration($groupedSolutionSimulation);
            }
        }

        return [
            $availableSimulation,
            $groupedSolutionSimulations,
            $singleSolutionSimulations
        ];
    }

    public function hookDisplayPaymentReturn($params)
    {
        if (!$this->active) {
            return '';
        }

        $order = (isset($params['objOrder'])) ? $params['objOrder'] : $params['order'];

        if (!Validate::isLoadedObject($order)) {
            return '';
        }

        /* @var Client $apiClient */
        /* @var SolutionNameHandler $solutionNameHandler */
        $apiClient = $this->get('scalexpert.api.client');
        $solutionNameHandler = $this->get('scalexpert.handler.solution_name');
        $financialSubscriptions = $apiClient->getFinancingSubscriptionsByOrderReference($order->reference);

        if (empty($financialSubscriptions)) {
            return '';
        }

        foreach ($financialSubscriptions as &$financialSubscription) {
            if (isset($financialSubscription['consolidatedStatus'])) {

                $financialSubscription['consolidatedStatusError'] = false;
                if ('REJECTED' === $financialSubscription['consolidatedStatus']) {
                    $financialSubscription['consolidatedStatusError'] = true;
                }

                $financialSubscription['consolidatedStatus'] = $solutionNameHandler->getFinancialStateName(
                    $financialSubscription['consolidatedStatus'],
                    $this->getTranslator()
                );
            }

            if (!empty($financialSubscription['buyerFinancedAmount'])) {
                $financialSubscription['buyerFinancedAmount'] = $this->context->getCurrentLocale()->formatPrice(
                    $financialSubscription['buyerFinancedAmount'],
                    Currency::getIsoCodeById((int)$order->id_currency)
                ) ?? '';
            }
        }

        $this->smarty->assign([
            'link' => $this->context->link,
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
            /* @var CartInsuranceProductsService $cartInsuranceProductsService */
            $cartInsuranceProductsService = $this->get('scalexpert.service.cart_insurance_products');
            $cartInsuranceProductsService->handleCartSave($params['cart']);
        }
    }

    public function hookActionFrontControllerSetMedia($params)
    {
        if (!empty($this->context->controller->php_self)) {
            switch ($this->context->controller->php_self) {
                case 'product' :
                    $this->_getProductMedia();
                    break;
                case 'cart' :
                    $this->_getCartMedia();
                    break;
                default:
                    break;
            }
        }
    }

    private function _getProductMedia(): void
    {
        Media::addJsDef([
            'getFinancialInsertsOnProductAjaxURL' => $this->context->link->getModuleLink(
                $this->name,
                'ajax',
                [
                    'ajax' => true,
                    'action' => 'GetFinancingSimulationInsertOnProduct'
                ]
            ),
            'getInsuranceInsertsAjaxURL' => $this->context->link->getModuleLink(
                $this->name,
                'ajax',
                [
                    'ajax' => true,
                    'action' => 'GetInsuranceInserts'
                ]
            )
        ]);
    }

    private function _getCartMedia(): void
    {
        Media::addJsDef([
            'getFinancialInsertsOnCartAjaxURL' => $this->context->link->getModuleLink(
                $this->name,
                'ajax',
                [
                    'ajax' => true,
                    'action' => 'GetFinancialInsertsOnCart'
                ]
            ),
            'getInsuranceInsertsAjaxURL' => $this->context->link->getModuleLink(
                $this->name,
                'ajax',
                [
                    'ajax' => true,
                    'action' => 'GetInsuranceInserts'
                ]
            )
        ]);
    }

    public function hookDisplayAdminOrderSide(array $params): string
    {
        if (empty($params['id_order'])) {
            return '';
        }

        $order = new Order($params['id_order']);
        if (!Validate::isLoadedObject($order)) {
            return '';
        }

        $trackingNumber = '';
        $orderCarrier = new OrderCarrier((int)$order->getIdOrderCarrier());
        if (Validate::isLoadedObject($orderCarrier)) {
            $trackingNumber = $orderCarrier->tracking_number;
        }

        /* @var Client $apiClient */
        $apiClient = $this->get('scalexpert.api.client');
        $financialSubscriptions = $apiClient->getFinancingSubscriptionsByOrderReference($order->reference);
        $financialSubscriptions = $this->getFinancialSubscriptionsTemplateData(
            $financialSubscriptions,
            (int)$order->id_currency
        );

        /* @var \Doctrine\ORM\EntityManager $entityManager */
        $entityManager = $this->get('doctrine.orm.entity_manager');
        $cartInsuranceRepository = $entityManager->getRepository(ScalexpertCartInsurance::class);
        $cartInsurances = $cartInsuranceRepository->findBy(['idCart' => $order->id_cart]);

        if (Tools::getIsset('scalexpert_cancel_financial_subscription')) {
            $financialSubscriptionId = Tools::getValue('scalexpert_cancel_financial_subscription_id');
            $financialSubscriptionAmount = (float)Tools::getValue('scalexpert_cancel_financial_subscription_amount');

            /* @var SubscriptionCanceler $subscriptionCanceler */
            $subscriptionCanceler = $this->get('scalexpert.service.subscription_canceler');
            $subscriptionCanceler->cancelFinancialSubscription(
                $financialSubscriptionId,
                $financialSubscriptionAmount,
                $this->getTranslator(),
                $this->get('session')->getFlashBag(),
                $this->get('router')
            );
        }

        if (Tools::getIsset('scalexpert_deliver_financial_subscription')) {
            $financialSubscriptionId = Tools::getValue('scalexpert_deliver_financial_subscription_id', '');
            $financialSubscriptionOperator = Tools::getValue('scalexpert_deliver_financial_subscription_operator', '');

            /* @var SubscriptionDeliverer $subscriptionDeliverer */
            $subscriptionDeliverer = $this->get('scalexpert.service.subscription_deliverer');
            $subscriptionDeliverer->deliverFinancialSubscription(
                $financialSubscriptionId,
                $financialSubscriptionOperator,
                $trackingNumber,
                $financialSubscriptions,
                $this->getTranslator(),
                $this->get('session')->getFlashBag(),
                $this->get('router')
            );
        }

        $templateData = [];
        $templateData['financialSubscriptions'] = $financialSubscriptions;
        $templateData['insuranceSubscriptionsByProduct'] = $this->getInsuranceSubscriptionsTemplateData(
            $cartInsurances,
            (int)$order->id_currency
        );

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

        return $this->get('twig')->render(
            '@Modules/' . $this->name . '/views/templates/admin/product-extra-fields.html.twig',
            [
                'productCustomFields' => $productCustomFields ?? [],
            ]
        );
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
            $productCustomField->setIdProduct((int)$productId);

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
        // Delete expired insurance product
        $cart = $params['cart'];
        if (!Validate::isLoadedObject($cart)) {
            return '';
        }

        $apiClient = $this->get('scalexpert.api.client');
        $entityManager = $this->get('doctrine.orm.entity_manager');
        $cartInsuranceRepository = $entityManager->getRepository(ScalexpertCartInsurance::class);
        $cartInsurances = $cartInsuranceRepository->findBy([
            'idCart' => $cart->id
        ]);

        foreach ($cartInsurances as $cartInsurance) {
            /** @var ScalexpertCartInsurance $cartInsurance */
            $product = new \Product($cartInsurance->getIdProduct());
            $insurances = $apiClient->getInsurancesByItemId(
                $cartInsurance->getSolutionCode(),
                $product->getPrice(),
                $cartInsurance->getIdItem()
            );

            if (!empty($insurances)) {
                foreach ($insurances as $insurance) {
                    if (
                        !empty($insurance['id'])
                        && $insurance['id'] === $cartInsurance->getIdInsurance()
                    ) {
                        $currentInsurance = $insurance;
                    }
                }
            }

            if (!empty($currentInsurance)) {
                $insuranceProduct = new \Product($cartInsurance->getIdInsuranceProduct());
                $insuranceProduct->price = \Tools::ps_round((float)$currentInsurance['price'], 5);
                $insuranceProduct->save();
            }
        }

        if (!empty(Tools::getValue('insurances'))) {
            /* @var CartInsuranceProductsService $cartInsuranceProductsService */
            $cartInsuranceProductsService = $this->get('scalexpert.service.cart_insurance_products');
            $cartInsuranceProductsService->handleInsuranceProductsFormSubmit($params['cart']);
            $cartInsuranceProductsService->handleCartQty($params['cart']);
            $cartInsuranceProductsService->handleQuotationsInCart($params['cart']);

            Tools::redirect($this->context->link->getPageLink('cart', null, null, ['action' => 'show']));
        }

        return $this->fetch('module:' . $this->name . '/views/templates/hook/shopping-cart-footer.tpl');
    }

    public function hookDisplayAdminOrderTop($params)
    {
        if (empty($params['id_order'])) {
            return '';
        }

        $order = new Order($params['id_order']);
        if (!Validate::isLoadedObject($order)) {
            return '';
        }

        $warning = '';
        $apiClient = $this->get('scalexpert.api.client');
        $financialSubscriptions = $apiClient->getFinancingSubscriptionsByOrderReference($order->reference);
        if (!empty($financialSubscriptions)) {
            foreach ($financialSubscriptions as &$financialSubscription) {
                // Display warning message if order sate is paid but financing subscription has not been accepted
                $orderSate = $order->getCurrentOrderState();
                if (
                    null !== $orderSate
                    && $orderSate->paid
                    && !in_array(
                        $financialSubscription['consolidatedStatus'],
                        MappingConfigurationFormDataConfiguration::FINAL_FINANCING_STATES,
                        true
                    )
                ) {
                    $warning .= '<div class="alert alert-warning d-print-none" role="alert">
      <button type="button" class="close" data-dismiss="alert" aria-label="Close">
        <span aria-hidden="true"><i class="material-icons">close</i></span>
      </button>
              <div class="alert-text">
                      <p>';
                    $warning .= $this->trans(
                        'Order is paid but financing subscription is not in a finished state.',
                        []
                        , 'Modules.Scalexpertplugin.Admin'
                    );
                    $warning .= '</p>
                  </div>
          </div>';
                }
            }
        }

        return $warning;
    }

    public function hookDisplayHeader($params)
    {
        if (!$this->active) {
            return;
        }

        if (!empty($this->context->controller->php_self)) {
            // Check insurances are enabled
            $activeInsurances = false;
            $insuranceConfigurationFormDataConfiguration = $this->get('scalexpert.service.insurance_configuration');
            $insurances = $insuranceConfigurationFormDataConfiguration->getConfiguration();
            if (!empty($insurances)) {
                foreach ($insurances as $insurance) {
                    if ($insurance) {
                        $activeInsurances = true;
                        break;
                    }
                }
            }

            switch ($this->context->controller->php_self) {
                case 'product' :
                    $this->_getProductHeader($activeInsurances);
                    break;
                case 'cart' :
                    $this->_getCartHeader($activeInsurances);
                    break;
                case 'order' :
                    $this->_getOrderHeader();
                    break;
                default:
                    break;
            }
        }


        $this->context->controller->registerStylesheet(
            'frontSimulation',
            $this->_path . 'views/css/frontSimulation.css'
        );

        $this->context->controller->registerStylesheet(
            'frontContentModalCSS',
            $this->_path . 'views/css/frontContentModal.css'
        );

        $this->context->controller->registerStylesheet(
            'frontSimulationModal',
            $this->_path . 'views/css/frontSimulationModal.css'
        );
    }

    private function _getProductHeader(bool $activeInsurances): void
    {
        $this->context->controller->registerStylesheet(
            'frontProductAdditionalInfoCSS',
            $this->_path . 'views/css/frontProductAdditionalInfo.css'
        );

        $this->context->controller->registerStylesheet(
            'frontProductSimulation',
            $this->_path . 'views/css/frontProductSimulation.css'
        );

        $this->context->controller->registerJavascript(
            'frontProductAdditionalInfoJS',
            $this->_path . 'views/js/frontProductAdditionalInfo.js'
        );

        if ($activeInsurances) {
            $this->context->controller->registerJavascript(
                'frontProductAdditionalInfoInsuranceJS',
                $this->_path . 'views/js/frontProductAdditionalInfo-insurance.js'
            );

            $this->context->controller->registerStylesheet(
                'frontProductAdditionalInfoInsuranceCSS',
                $this->_path . 'views/css/frontProductAdditionalInfo-insurance.css'
            );
        }
    }

    private function _getCartHeader(bool $activeInsurances): void
    {
        $this->context->controller->registerJavascript(
            'frontCartSimulationJS',
            $this->_path . 'views/js/frontCartSimulation.js'
        );

        if ($activeInsurances) {
            $this->context->controller->registerJavascript(
                'frontCartInsuranceJS',
                $this->_path . 'views/js/frontCart-insurance.js'
            );

            $this->context->controller->registerStylesheet(
                'frontProductAdditionalInfoInsuranceCSS',
                $this->_path . 'views/css/frontProductAdditionalInfo-insurance.css'
            );
        }
    }

    private function _getOrderHeader(): void
    {
        $this->context->controller->registerStylesheet(
            'frontPaymentOptionsCSS',
            $this->_path . 'views/css/frontPaymentOptions.css'
        );

        $this->context->controller->registerStylesheet(
            'frontPaymentSimulation',
            $this->_path . 'views/css/frontPaymentSimulation.css'
        );

        $this->context->controller->registerJavascript(
            'frontPaymentOptionsJS',
            $this->_path . 'views/js/frontPaymentOptions.js'
        );

        $this->context->controller->registerJavascript(
            'frontPaymentSimulationJS',
            $this->_path . 'views/js/frontPaymentSimulation.js'
        );
    }

    public function hookActionOrderStatusPostUpdate($params)
    {
        /* @var InsurancesSubscriptionsService $insurancesSubscriptionsService */
        $insurancesSubscriptionsService = $this->get('scalexpert.service.insurances_subscriptions');
        $insurancesSubscriptionsService->createOrderInsurancesSubscriptions($params);
    }

    public function hookDisplayOrderConfirmation($params)
    {
        if (
            empty($params['order'])
            || !Validate::isLoadedObject($params['order'])
        ) {
            return '';
        }

        /* @var Client $apiClient */
        $apiClient = $this->get('scalexpert.api.client');
        $entityManager = $this->get('doctrine.orm.entity_manager');
        $cartInsuranceRepository = $entityManager->getRepository(ScalexpertCartInsurance::class);

        $cartInsurances = $cartInsuranceRepository->findBy(['idCart' => $params['order']->id_cart]);
        if (empty($cartInsurances)) {
            return '';
        }

        $insuranceSubscriptionsByProduct = [];
        foreach ($cartInsurances as $cartInsurance) {
            $insuredProduct = new Product(
                $cartInsurance->getIdProduct(),
                false,
                $this->context->language->id
            );
            $insuranceProduct = new Product(
                $cartInsurance->getIdInsuranceProduct(),
                false,
                $this->context->language->id
            );
            $subscriptions = $cartInsurance->getSubscriptions();
            $subscriptionsToAdd = [];

            if (!empty($subscriptions)) {
                foreach ($subscriptions as $subscriptionId) {
                    $apiSubscription = $apiClient->getInsuranceSubscriptionBySubscriptionId($subscriptionId);
                    if (empty($apiSubscription)) {
                        continue;
                    }

                    $subscriptionsToAdd[] = [
                        'subscriptionId' => $subscriptionId,
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

        $this->context->smarty->assign([
            'insuranceSubscriptionsByProduct' => $insuranceSubscriptionsByProduct,
        ]);

        return $this->fetch('module:' . $this->name . '/views/templates/hook/order-confirmation-insurances.tpl');
    }

    public function getFinancialSubscriptionsTemplateData(
        $financialSubscriptions,
        int $currencyId
    )
    {
        if (empty($financialSubscriptions)) {
            return [];
        }

        /* @var SolutionNameHandler $solutionNameHandler */
        $solutionNameHandler = $this->get('scalexpert.handler.solution_name');
        /* @var AvailableSolutionsService $availableSolutionsService */
        $availableSolutionsService = $this->get('scalexpert.service.available_solutions');

        foreach ($financialSubscriptions as &$financialSubscription) {
            if (!empty($financialSubscription['buyerFinancedAmount'])) {
                $financialSubscription['buyerFinancedAmountFloat'] = $financialSubscription['buyerFinancedAmount'];
                $financialSubscription['buyerFinancedAmount'] = $this->context->getCurrentLocale()->formatPrice(
                    $financialSubscription['buyerFinancedAmount'],
                    Currency::getIsoCodeById($currencyId)
                ) ?? '';
            }

            $financialSubscription['consolidatedStatusDisplay'] = $solutionNameHandler->getFinancialStateName(
                $financialSubscription['consolidatedStatus'],
                $this->getTranslator(),
                true
            );
            $financialSubscription['consolidatedSubstatusDisplay'] = $solutionNameHandler->getFinancialSubStateName(
                $financialSubscription['consolidatedSubstatus'],
                $this->getTranslator()
            );
            $financialSubscription['displayDeliveryConfirmation'] = $availableSolutionsService->displayDeliveryConfirmation(
                $financialSubscription['solutionCode'],
                (bool)$financialSubscription['isDelivered']
            );
            $financialSubscription['operators'] = $availableSolutionsService->getOperators(
                $financialSubscription['solutionCode'],
                $this->getTranslator()
            );
        }

        return $financialSubscriptions;
    }

    public function getInsuranceSubscriptionsTemplateData(
        array $cartInsurances,
        int $currencyId
    ): array
    {
        if (empty($cartInsurances)) {
            return [];
        }

        /* @var Client $apiClient */
        $apiClient = $this->get('scalexpert.api.client');
        /* @var InsuranceNameHandler $insuranceNameHandler */
        $insuranceNameHandler = $this->get('scalexpert.handler.insurance_name');

        $insuranceSubscriptionsByProduct = [];
        $idLang = $this->context->language->id;

        foreach ($cartInsurances as $cartInsurance) {
            $insuredProduct = new Product($cartInsurance->getIdProduct(), false, $idLang);
            $insuranceProduct = new Product($cartInsurance->getIdInsuranceProduct(), false, $idLang);
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
                        'consolidatedStatus' => $apiSubscription['consolidatedStatus'] ?
                            $insuranceNameHandler->getInsuranceStateName(
                                $apiSubscription['consolidatedStatus'],
                                $this->getTranslator()
                            ) : '',
                        'duration' => $apiSubscription['duration'] ?? '',
                        'producerQuoteInsurancePrice' => $this->context->getCurrentLocale()->formatPrice(
                                $apiSubscription['producerQuoteInsurancePrice'],
                                Currency::getIsoCodeById($currencyId)
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

        return $insuranceSubscriptionsByProduct;
    }
}
