<?php
/**
 * Copyright © Scalexpert.
 * This file is part of Scalexpert plugin for PrestaShop. See COPYING.md for license details.
 *
 * @author    Scalexpert (https://scalexpert.societegenerale.com/)
 * @copyright Scalexpert
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */


use ScalexpertPlugin\Api\Insurance;
use ScalexpertPlugin\Helper\FinancingEligibility;
use ScalexpertPlugin\Helper\FinancingNamer;
use ScalexpertPlugin\Helper\InsuranceEligibility;
use ScalexpertPlugin\Helper\InsuranceNamer;
use ScalexpertPlugin\Helper\InsuranceProcess;
use ScalexpertPlugin\Helper\SimulationFormatter;
use ScalexpertPlugin\Helper\SolutionManager;
use ScalexpertPlugin\Model\CartInsurance;
use ScalexpertPlugin\Model\ProductField;
use ScalexpertPlugin\Model\FinancingOrder;
use ScalexpertPlugin\Api\Financing;
use PrestaShop\PrestaShop\Core\Payment\PaymentOption;
use ScalexpertPlugin\Service\DataBuilder;
use ScalexpertPlugin\Service\OrderUpdater;
use ScalexpertPlugin\Service\SolutionSorter;

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

    const ORDER_STATES = [
        0 => [
            'configuration' => 'SCALEXPERT_ORDER_STATE_WAITING',
            'name' => 'En attente de financement',
            'color' => '#4169E1',
            'logable' => false,
            'paid' => false,
            'invoice' => false,
            'shipped' => false,
            'delivery' => false,
            'pdf_delivery' => false,
            'pdf_invoice' => false,
            'send_email' => false,
            'hidden' => false,
            'unremovable' => true,
            'template' => '',
            'deleted' => false,
        ],
    ];

    const API_URL_PROD = 'SCALEXPERT_API_PRODUCTION_URL';
    const API_URL_TEST = 'SCALEXPERT_API_TEST_URL';

    public function __construct()
    {
        $this->name = 'scalexpertplugin';
        $this->tab = 'payments_gateways';
        $this->version = '1.5.0';
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

    public function install(): bool
    {
        if (!extension_loaded('curl')) {
            $this->_errors[] = $this->l('You have to enable the cURL extension on your server to install this module');
            return false;
        }

        Configuration::updateValue('SCALEXPERT_ENVIRONMENT', 'test');

        return parent::install()
            && $this->installTabs()
            && $this->installDatabase()
            && $this->installCategoryInsurance()
            && $this->createFinancingOrderStates()
            && $this->createApiUrls()
            && $this->createMeta()
            && $this->generateDefaultMapping()
            && $this->registerHooks();
    }

    private function registerHooks(): bool
    {
        $hooks = [
            'header',
            'displayBackOfficeHeader',
            'displayProductButtons',
            'displayShoppingCartFooter',
            'displayOrderConfirmation',
            'displayAdminProductsExtra',
            'actionCartSave',
            'actionProductSave',
            'displayAdminOrderTop',
            'displayAdminOrder',
            'displayAdminOrderSide',
            'actionOrderStatusPostUpdate',
            'payment',
            'paymentReturn',
            'paymentOptions'
        ];

        $registerOk = true;
        foreach ($hooks as $hook) {
            $registerOk &= $this->registerHook($hook);
        }

        return $registerOk;
    }

    public function installTabs(): bool
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
            'AdminScalexpertCustomize' => [
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

    public function installDatabase(): bool
    {
        return ProductField::createTable()
            && CartInsurance::createTable()
            && FinancingOrder::createTable();
    }

    public function installCategoryInsurance(): bool
    {
        $insuranceProductsCategory = new Category();
        $rootCategory = Category::getRootCategory();
        if (Validate::isLoadedObject($rootCategory)) {
            $insuranceProductsCategory->id_parent = $rootCategory->id;
        }

        $insuranceProductsCategoryData = [
            'en' => 'Insurance Products (do not delete)',
            'fr' => 'Produits d\'assurance (ne pas supprimer)',
            'de' => 'Versicherungsprodukte (nicht löschen)',
        ];

        $insuranceProductsCategoryNames = [];
        $insuranceProductsCategoryLinkRewrites = [];

        foreach (Language::getLanguages() as $lang) {
            if (!empty($insuranceProductsCategoryData[$lang['iso_code']])) {
                $insuranceProductsCategoryNames[$lang['id_lang']] = $insuranceProductsCategoryData[$lang['iso_code']];
                $insuranceProductsCategoryLinkRewrites[$lang['id_lang']] = Tools::str2url($insuranceProductsCategoryData[$lang['iso_code']]);
            } else {
                $insuranceProductsCategoryNames[$lang['id_lang']] = $insuranceProductsCategoryData['en'];
                $insuranceProductsCategoryLinkRewrites[$lang['id_lang']] = Tools::str2url($insuranceProductsCategoryData['en']);
            }
        }

        $insuranceProductsCategory->name = $insuranceProductsCategoryNames;
        $insuranceProductsCategory->active = false;
        $insuranceProductsCategory->is_root_category = false;
        $insuranceProductsCategory->link_rewrite = $insuranceProductsCategoryLinkRewrites;

        $creationResult = $insuranceProductsCategory->save();
        if ($creationResult) {
            Configuration::updateValue(
                InsuranceProcess::INSURANCE_CATEGORY_CONFIG_NAME,
                $insuranceProductsCategory->id
            );
        }

        return !empty($creationResult);
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
                    $orderState = new OrderState((int)$idOrderState);
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

                $orderStateAdd = (bool)$orderState->save();
                $createFinancingOrderStates &= $orderStateAdd;

                if ($orderStateAdd) {
                    Configuration::updateValue($orderStateToAdd['configuration'], $orderState->id);
                }
            }
        }

        return $createFinancingOrderStates;
    }

    public function createApiUrls(): bool
    {
        return
            Configuration::updateValue(
                static::API_URL_PROD,
                'https://api.scalexpert.societegenerale.com/baas/prod'
            )
            && Configuration::updateValue(
                static::API_URL_TEST,
                'https://api.scalexpert.uatc.societegenerale.com/baas/uatc'
            );
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

        $titles = [];
        $url_rewrites = [];
        $languages = \Language::getLanguages();
        foreach ($languages as $lang) {
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

    public function uninstall(): bool
    {
        return parent::uninstall()
            && $this->uninstallTabs()
            && $this->uninstallDatabase()
            && $this->uninstallConfigVars();
    }

    public function uninstallFinancingOrderStates(): bool
    {
        if (!empty(self::ORDER_STATES)) {
            foreach (self::ORDER_STATES as $orderStateToAdd) {
                $idOrderState = Configuration::get($orderStateToAdd['configuration']);
                if ($idOrderState) {
                    $orderState = new OrderState((int)$idOrderState);
                    $orderState->delete();
                }
            }
        }

        return true;
    }

    public function uninstallTabs(): bool
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

    public function uninstallConfigVars(): bool
    {
        $vars = [
            'SCALEXPERT_ENVIRONMENT',
            'SCALEXPERT_API_TEST_IDENTIFIER',
            'SCALEXPERT_API_TEST_KEY',
            'SCALEXPERT_API_PRODUCTION_IDENTIFIER',
            'SCALEXPERT_API_PRODUCTION_KEY',
            'SCALEXPERT_DEBUG_MODE',
            'SCALEXPERT_FINANCING_SOLUTIONS',
            'SCALEXPERT_INSURANCE_SOLUTIONS',
            'SCALEXPERT_GROUP_FINANCING_SOLUTIONS',
            'SCALEXPERT_CUSTOMIZE_PRODUCT',
            'SCALEXPERT_ORDER_STATE_ACCEPTED',
            InsuranceProcess::INSURANCE_CATEGORY_CONFIG_NAME,
            'SCALEXPERT_ORDER_STATE_MAPPING',
            self::API_URL_PROD,
            self::API_URL_TEST
        ];

        foreach ($vars as $name) {
            Configuration::deleteByName($name);
        }

        return true;
    }

    public function uninstallDatabase(): bool
    {
        return ProductField::deleteTable()
            && CartInsurance::deleteTable()
        ;
    }

    public function hookHeader()
    {
        if (
            !version_compare(_PS_VERSION_, '1.7', '>=')
            && isset($_GET['phoneError'])
        ) {
            \Context::getContext()->controller->errors[] = $this->l('Please provide a valid phone number to select this payment method');
        }

        // Check insurances are enabled
        $activeInsurances = false;
        $insurances = InsuranceEligibility::getConfigSolutions();
        if (!empty($insurances)) {
            foreach ($insurances as $insurance) {
                if ('1' === $insurance) {
                    $activeInsurances = true;
                    break;
                }
            }
        }

        if (empty($this->context->controller->php_self)) {
            return;
        }

        switch ($this->context->controller->php_self) {
            case 'product' :
                $this->_getProductHeader($activeInsurances);
                break;
            case 'cart' :
                $this->_getCartHeader($activeInsurances);
                break;
            case 'order' :
                $this->_getOrderHeader($activeInsurances);
                break;
            default:
                break;
        }

        if (version_compare(_PS_VERSION_, '1.7', '>=')) {
            $this->context->controller->registerStylesheet(
                'frontContentModalCSS',
                $this->_path . 'views/css/ps17/frontContentModal.css'
            );
            $this->context->controller->registerStylesheet(
                'frontSimulation',
                $this->_path . 'views/css/ps17/frontSimulation.css'
            );
            $this->context->controller->registerStylesheet(
                'frontSimulationModal',
                $this->_path . 'views/css/ps17/frontSimulationModal.css'
            );
        } else {
            $this->context->controller->addCSS($this->_path . 'views/css/ps16/frontGlobal.css');
            $this->context->controller->addCSS($this->_path . 'views/css/ps16/frontSimulation.css');
            $this->context->controller->addCSS($this->_path . 'views/css/ps16/frontSimulationModal.css');
        }
    }

    private function _getProductHeader($activeInsurances)
    {
        if (version_compare(_PS_VERSION_, '1.7', '>=')) {
            Media::addJsDef([
                'scalexpertpluginProductId' => (int)Tools::getValue('id_product'),
                'scalexpertpluginProductAttributeId' => (int)Tools::getValue('id_product_attribute'),
                'scalexpertpluginFrontUrl' => $this->context->link->getModuleLink('scalexpertplugin', 'display')
            ]);
            $this->context->controller->registerStylesheet(
                'frontProductButtonsCSS',
                $this->_path . 'views/css/ps17/frontProductButtons.css'
            );

            $this->context->controller->registerStylesheet(
                'frontProductSimulation',
                $this->_path . 'views/css/ps17/frontProductSimulation.css'
            );

            $this->context->controller->registerJavascript(
                'frontProductButtonsJS',
                $this->_path . 'views/js/ps17/frontProductButtons.js'
            );

            if ($activeInsurances) {
                $this->context->controller->registerJavascript(
                    'frontProductButtonsInsuranceJS',
                    $this->_path . 'views/js/ps17/frontProductButtons-insurance.js'
                );

                $this->context->controller->registerStylesheet(
                    'frontProductAdditionalInfoInsuranceCSS',
                    $this->_path . 'views/css/ps17/frontProductAdditionalInfo-insurance.css'
                );
            }
        } else {
            $this->context->controller->addJS($this->_path . 'views/js/ps16/frontProductButtons.js');
            $this->context->controller->addCSS($this->_path . 'views/css/ps16/frontProductButtons.css');
            $this->context->controller->addCSS($this->_path . 'views/css/ps16/frontProductSimulation.css');

            if ($activeInsurances) {
                $this->context->controller->addJS($this->_path . 'views/js/ps16/frontProductButtons-insurance.js');
                $this->context->controller->addCSS($this->_path . 'views/css/ps16/frontProductButtons-insurance.css');
            }
        }
    }

    private function _getCartHeader($activeInsurances)
    {
        if (version_compare(_PS_VERSION_, '1.7', '>=')) {
            Media::addJsDef([
                'scalexpertpluginFrontUrl' => $this->context->link->getModuleLink('scalexpertplugin', 'display')
            ]);

            $this->context->controller->registerJavascript(
                'frontCartSimulationJS',
                $this->_path . 'views/js/ps17/frontCartSimulation.js'
            );

            if ($activeInsurances) {

                $this->context->controller->registerJavascript(
                    'frontCartInsuranceJS',
                    $this->_path . 'views/js/ps17/frontCart-insurance.js'
                );

                $this->context->controller->registerStylesheet(
                    'frontProductAdditionalInfoInsuranceCSS',
                    $this->_path . 'views/css/ps17/frontProductAdditionalInfo-insurance.css'
                );
            }
        }
    }

    private function _getOrderHeader($activeInsurances)
    {
        if (version_compare(_PS_VERSION_, '1.7', '>=')) {
            Media::addJsDef([
                'scalexpertpluginFrontUrl' => $this->context->link->getModuleLink('scalexpertplugin', 'display')
            ]);
            $this->context->controller->registerStylesheet(
                'frontPaymentOptionsCSS',
                $this->_path . 'views/css/ps17/frontPaymentOptions.css'
            );

            $this->context->controller->registerJavascript(
                'frontPaymentOptionsJS',
                $this->_path . 'views/js/ps17/frontPaymentOptions.js'
            );

            $this->context->controller->registerJavascript(
                'frontPaymentSimulation',
                $this->_path . 'views/js/ps17/frontPaymentSimulation.js'
            );
        } else {
            $this->context->controller->addCSS($this->_path . 'views/css/ps16/frontPayment.css');

            /* First step order */
            if (
                property_exists($this->context->controller, 'step')
                && $this->context->controller->step === 0
            ) {
                $this->context->controller->addJS($this->_path . 'views/js/ps16/frontCartSimulation.js');
                if($activeInsurances) {
                    $this->context->controller->addJS($this->_path . 'views/js/ps16/frontCart-insurance.js');
                    $this->context->controller->addCSS($this->_path . 'views/css/ps16/frontProductButtons-insurance.css');
                }
            }
        }
    }

    public function hookDisplayProductButtons($params): string
    {
        if (version_compare(_PS_VERSION_, '1.7', '>=')) {
            return $this->display(__FILE__, 'views/templates/hook/ps17/hookDisplayProductButtons.tpl');
        }

        $this->context->smarty->assign([
            'scalexpertpluginCartId' => \Context::getContext()->cart->id,
            'scalexpertpluginProductId' => (int)Tools::getValue('id_product'),
            'scalexpertpluginFrontUrl' => $this->context->link->getModuleLink('scalexpertplugin', 'display'),
            'scalexpertpluginAddToCartUrl' => $this->context->link->getModuleLink('scalexpertplugin', 'display')
        ]);

        return $this->display(__FILE__, 'views/templates/hook/ps16/hookDisplayProductButtons.tpl');
    }

    public function hookDisplayShoppingCartFooter($params): string
    {
        // Delete expired insurance product
        $cart = $params['cart'];
        if (Validate::isLoadedObject($cart)) {
            $cartInsurances = CartInsurance::getInsuranceByIdCart($cart->id);
            foreach ($cartInsurances as $cartInsurance) {
                $product = new \Product($cartInsurance['id_product']);
                $insurances = Insurance::searchInsurances(
                    $cartInsurance['solution_code'],
                    $cartInsurance['id_item'],
                    $product->getPrice()
                );

                if (!empty($insurances['insurances'])) {
                    foreach ($insurances['insurances'] as $insurance) {
                        if (
                            !empty($insurance['id'])
                            && (int)$insurance['id'] === (int)$cartInsurance['id_insurance']
                        ) {
                            $currentInsurance = $insurance;
                        }
                    }
                }

                if (!empty($currentInsurance)) {
                    $insuranceProduct = new \Product($cartInsurance['id_insurance_product']);
                    if (version_compare(_PS_VERSION_, '1.7', '>=')) {
                        $insuranceProduct->price = (float)$currentInsurance['price'];
                    } else {
                        // Update price by removing taxe
                        $address = \Address::initialize(null);
                        $id_tax_rules = (int)\Product::getIdTaxRulesGroupByIdProduct(
                            $insuranceProduct->id,
                            \Context::getContext()
                        );
                        $tax_manager = \TaxManagerFactory::getManager($address, $id_tax_rules);
                        $tax_calculator = $tax_manager->getTaxCalculator();

                        $newPrice = $tax_calculator->removeTaxes((float)$currentInsurance['price']);
                        $insuranceProduct->price = Tools::ps_round((float)$newPrice, 5);
                    }

                    $insuranceProduct->save();
                }
            }
        }

        if (!empty(Tools::getValue('insurances'))) {
            InsuranceProcess::handleCartSave($params);

            if (version_compare(_PS_VERSION_, '1.7', '>=')) {
                Tools::redirect($this->context->link->getPageLink('cart', null, null, ['action' => 'show']));
            } else {
                Tools::redirect('index.php?controller=order&step=1');
            }

        }

        if (version_compare(_PS_VERSION_, '1.7', '>=')) {
            return $this->display(__FILE__, 'views/templates/hook/ps17/hookDisplayShoppingCartFooter.tpl');

        }

        $this->context->smarty->assign([
            'scalexpertpluginFrontUrl' => $this->context->link->getModuleLink('scalexpertplugin', 'display'),
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
            property_exists($this->context->controller, 'module')
            && !empty($this->context->controller->module->name)
            && $this->context->controller->module->name === $this->name
        ) {
            $this->context->controller->addJquery();
            $this->context->controller->addJS($this->_path . 'views/js/back.js');
            $this->context->controller->addCSS('/js/jquery/plugins/autocomplete/jquery.autocomplete.css');
            $this->context->controller->addJS('/js/jquery/plugins/autocomplete/jquery.autocomplete.js');
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

        $customizeProduct = FinancingEligibility::getEligibleSolutionByCart($oCart);
        if (empty($customizeProduct)) {
            return false;
        }

        $eligibleSolutions = Financing::getEligibleSolutionsForFront(
            $oCart->getOrderTotal(),
            strtoupper($this->context->language->iso_code)
        );

        foreach ($eligibleSolutions as $solutionCode => $eligibleSolution) {
            if (isset($customizeProduct[$eligibleSolution['solutionCode']])) {
                if (!empty($customizeProduct[$eligibleSolution['solutionCode']]['title_payment'])) {
                    $eligibleSolutions[$solutionCode]['communicationKit']['visualTitle'] = $customizeProduct[$eligibleSolution['solutionCode']]['title_payment'];
                }

                $eligibleSolutions[$solutionCode]['communicationKit']['displayLogo'] = $customizeProduct[$eligibleSolution['solutionCode']]['logo_payment'];
                $eligibleSolutions[$solutionCode]['position'] = $customizeProduct[$eligibleSolution['solutionCode']]['position'];
            } else {
                unset($eligibleSolutions[$solutionCode]);
            }
        }

        // Sort products by position
        SolutionSorter::sortSolutionsByPosition($eligibleSolutions);

        $this->smarty->assign([
            'module_dir' => $this->_path,
            'eligibleSolutions' => $eligibleSolutions,
        ]);

        return $this->display(__FILE__, 'views/templates/hook/payment.tpl');
    }

    /**
     * Return payment options available for PS 1.7+
     *
     * @param array $params Hook parameters
     *
     * @return array
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function hookPaymentOptions($params): array
    {
        if (
            !$this->active
            || !$this->checkCurrency($params['cart'])
        ) {
            return [];
        }

        /** @var Cart $oCart */
        $oCart = $params['cart'];

        $customizeProduct = FinancingEligibility::getEligibleSolutionByCart($oCart);
        if (empty($customizeProduct)) {
            return [];
        }

        $eligibleSolutions = Financing::getEligibleSolutionsForFront(
            $oCart->getOrderTotal(),
            strtoupper($this->context->language->iso_code)
        );

        if (!$eligibleSolutions) {
            return [];
        }

        $designData = DataBuilder::buildDesignData($eligibleSolutions, $customizeProduct, true);
        $redirectControllerLink = $this->context->link->getModuleLink($this->name, 'validation', [], true);
        $regroupPayments = (int)Configuration::get('SCALEXPERT_GROUP_FINANCING_SOLUTIONS');
        $availableOptions = [];

        $this->smarty->assign(
            $this->name . '_global',
            [
                'nameModule' => $this->name
            ]
        );

        $simulateResponse = Financing::simulateFinancing(
            $oCart->getOrderTotal(),
            strtoupper($this->context->language->iso_code),
            $designData['solutionCodes']
        );

        $singleSolutionSimulations = SimulationFormatter::normalizeSimulations(
            $simulateResponse['data'],
            $designData['designSolutions']
        );
        $groupedSolutionSimulations = SimulationFormatter::normalizeSimulations(
            $simulateResponse['data'],
            $designData['designSolutions'],
            true,
            true
        );

        foreach ($eligibleSolutions as $k => &$eligibleSolutionTemp) {
            if (!empty($eligibleSolutionTemp['communicationKit'])) {
                $eligibleSolutionTemp = $eligibleSolutionTemp['communicationKit'];

                if (isset($customizeProduct[$eligibleSolutionTemp['solutionCode']])) {
                    if (!empty($customizeProduct[$eligibleSolutionTemp['solutionCode']]['title_payment'])) {
                        $eligibleSolutionTemp['visualTitle'] = $customizeProduct[$eligibleSolutionTemp['solutionCode']]['title_payment'];
                    }

                    $eligibleSolutionTemp['useLogo'] = 0;
                    if (!empty($customizeProduct[$eligibleSolutionTemp['solutionCode']]['logo_payment'])) {
                        $eligibleSolutionTemp['useLogo'] = 1;
                    }

                    $eligibleSolutionTemp['position'] = $customizeProduct[$eligibleSolutionTemp['solutionCode']]['position'];

                    if (
                        !$simulateResponse['hasError']
                        && isset($simulateResponse['data']['solutionSimulations'])
                    ) {
                        $eligibleSolutionTemp['simulation'] =
                            $singleSolutionSimulations[$eligibleSolutionTemp['solutionCode']] ?? '';
                        $eligibleSolutionTemp['simulationPopinData'] =
                            $groupedSolutionSimulations[$eligibleSolutionTemp['solutionCode']] ?? '';
                    }
                } else {
                    unset($eligibleSolutions[$k]);
                }
            }
        }
        // Sort products by position
        SolutionSorter::sortSolutionsByPosition($eligibleSolutions);

        if (!empty($regroupPayments)) {
            $this->smarty->assign([
                'availableFinancialSolutions' => $eligibleSolutions,
                'redirectControllerLink' => $redirectControllerLink,
                'financedAmountFormatted' => \Tools::displayPrice(
                    (float)$simulateResponse['data']['financedAmount']
                ),
            ]);

            $availableOption = new PaymentOption();
            $availableOption->setModuleName($this->name)
                ->setCallToActionText(
                    $this->l('Pay your purchase by installments')
                )
                ->setAction($redirectControllerLink)
                ->setAdditionalInformation(
                    $this->fetch(
                        'module:' . $this->name . '/views/templates/hook/ps17/regroupedPayments.tpl'
                    )
                )
                ->setBinary(true);

            $availableOptions[] = $availableOption;
        } else {
            foreach ($eligibleSolutions as $k => $eligibleSolution) {
                $this->smarty->assign([
                    'availableSolution' => $eligibleSolution,
                    'financedAmountFormatted' => \Tools::displayPrice(
                        (float)$simulateResponse['data']['financedAmount']
                    ),
                ]);

                $availableOption = new PaymentOption();
                $availableOption->setModuleName($this->name)
                    ->setCallToActionText(strip_tags($eligibleSolution['visualTitle']))
                    ->setAction(
                        $this->context->link->getModuleLink($this->name, 'validation', [
                            'solutionCode' => $k
                        ], true)
                    )
                    ->setAdditionalInformation(
                        $this->fetch(
                            'module:' . $this->name . '/views/templates/hook/ps17/paymentAdditionalInformation.tpl'
                        )
                    );

                if ($eligibleSolution['useLogo']) {
                    $availableOption->setLogo($eligibleSolution['visualLogo']);
                }

                $availableOptions[] = $availableOption;
            }
        }

        return $availableOptions;
    }

    public function hookDisplayOrderConfirmation($params): string
    {
        if (false === $this->active) {
            return '';
        }

        if (version_compare(_PS_VERSION_, '1.7', '>=')) {
            $order = $params['order'] ?? $params['objOrder'];
        } else {
            $order = $params['objOrder'];
        }

        $insuranceSubscriptionsByProduct = $this->getOrderInsurances($order);

        $this->context->smarty->assign([
            'insuranceSubscriptionsByProduct' => $insuranceSubscriptionsByProduct,
        ]);

        if (version_compare(_PS_VERSION_, '1.7', '>=')) {
            return $this->display(__FILE__, 'views/templates/hook/ps17/confirmation-insurances.tpl');
        }

        return $this->display(__FILE__, 'views/templates/hook/ps16/confirmation-insurances.tpl');
    }

    /**
     * This hook is used to display the order confirmation page.
     */
    public function hookPaymentReturn($params): string
    {
        if (false === $this->active) {
            return '';
        }

        if (version_compare(_PS_VERSION_, '1.7', '>=')) {
            $order = $params['order'] ?? $params['objOrder'];
            $total = Tools::displayPrice($order->total_paid, $this->context->currency);
        } else {
            $order = $params['objOrder'];
            $total = Tools::displayPrice($params['total_to_pay'], $params['currencyObj']);
        }
        if ((int)$order->getCurrentOrderState()->id !== (int)Configuration::get('PS_OS_ERROR')) {
            $this->smarty->assign('status', 'ok');
        }

        $idSubscription = FinancingOrder::get($order->id);
        $subscriptionInfo = Financing::getSubscriptionInfo($idSubscription);

        $status = $this->l('Unknown state');
        if (isset($subscriptionInfo['consolidatedStatus'])) {
            $status = FinancingNamer::getFinancialStateName($subscriptionInfo['consolidatedStatus'], $this);
        }

        try {
            OrderUpdater::updateOrderStateBasedOnFinancingStatus($order, $subscriptionInfo['consolidatedStatus']);
        } catch (\Exception $e) {
            \PrestaShopLogger::addLog(
                '[' . $this->name . '] Error while hookPaymentReturn: ' . $e->getMessage(),
                3,
                null,
                'Order',
                $order->id,
                true
            );
        }

        $this->smarty->assign(array(
            'id_order' => $order->id,
            'reference' => $order->reference,
            'id_subscription' => $idSubscription,
            'subscription_status' => $status,
            'subscription_status_error' => ('REJECTED' === (string)$subscriptionInfo['consolidatedStatus']),
            'params' => $params,
            'total' => $total,
            'shop_name' => $this->context->shop->name,
        ));

        if (version_compare(_PS_VERSION_, '1.7', '>=')) {
            return $this->display(__FILE__, 'views/templates/hook/ps17/confirmation.tpl');
        }

        return $this->display(__FILE__, 'views/templates/hook/ps16/confirmation.tpl');
    }

    public function hookDisplayAdminProductsExtra($params): string
    {
        if (isset($params['id_product'])) {
            $idProduct = (int)$params['id_product'];
        } else {
            $idProduct = (int)Tools::getValue('id_product');
        }
        if (!$idProduct) {
            return '';
        }

        $modelValues = [];
        $characteristicsValues = [];
        $data = ProductField::getAllData($idProduct);
        foreach ($data as $element) {
            $modelValues[$element['id_lang']] = $element['model'];
            $characteristicsValues[$element['id_lang']] = $element['characteristics'];
        }

        $this->context->smarty->assign(array(
            'languages' => Language::getLanguages(false),
            'scalexpertplugin_model_values' => $modelValues,
            'scalexpertplugin_characteristics_values' => $characteristicsValues,
        ));

        return $this->display(__FILE__, '/hookDisplayAdminProductsExtra.tpl');
    }

    public function hookActionProductSave($params)
    {
        if (isset($params['id_product'])) {
            $idProduct = (int)$params['id_product'];
        } else {
            $idProduct = (int)Tools::getValue('id_product');
        }
        if (!$idProduct) {
            return;
        }

        if (!Tools::getIsset('scalexpertplugin_model')) {
            return;
        }

        $characteristics = Tools::getValue('scalexpertplugin_characteristics');
        $model = Tools::getValue('scalexpertplugin_model');
        foreach ($model as $idLang => $valueModel) {
            ProductField::saveData(
                $idProduct,
                $idLang,
                $valueModel,
                $characteristics[$idLang]
            );
        }
    }

    public function hookActionCartSave($params)
    {
        InsuranceProcess::handleCartSave($params);
    }

    public function hookDisplayAdminOrderTop($params): string
    {
        if (empty($params['id_order'])) {
            return '';
        }
        $order = new Order($params['id_order']);
        if (!Validate::isLoadedObject($order)) {
            return '';
        }

        $warning = '';
        $financialSubscriptions = $this->getOrderSubscriptions($order);
        if (!empty($financialSubscriptions)) {
            foreach ($financialSubscriptions as &$element) {
                // Display warning message if order sate is paid but financing subscription has not been accepted
                $orderSate = $order->getCurrentOrderState();
                if (
                    null !== $orderSate
                    && $orderSate->paid
                    && !in_array($element['consolidatedStatus'], Financing::$finalFinancingStates, true)
                ) {
                    $warning .= '<div class="alert alert-warning d-print-none" role="alert">
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true"><i class="material-icons">close</i></span>
                        </button>
                        <div class="alert-text">
                              <p>';
                    $warning .= $this->l('Order is paid but financing subscription is not in a finished state.');
                    $warning .= '</p>
                          </div>
                        </div>';
                }
            }
        }

        return $warning;
    }

    public function hookDisplayAdminOrder(array $params): string
    {
        if (
            version_compare(_PS_VERSION_, '1.7.7.0', '>=')
            || empty($params['id_order'])
        ) {
            return '';
        }

        $order = new Order($params['id_order']);
        if (!Validate::isLoadedObject($order)) {
            return '';
        }

        $trackingNumber = $this->setTrackingNumberAndOperators($order->getIdOrderCarrier());

        $financialSubscriptions = $this->getOrderSubscriptions($order);
        if (!empty($financialSubscriptions)) {
            foreach ($financialSubscriptions as &$element) {
                // Display warning message if order sate is paid but financing subscription has not been accepted
                $orderSate = $order->getCurrentOrderState();
                if (
                    null !== $orderSate
                    && $orderSate->paid
                    && !in_array(
                        $element['consolidatedStatus'],
                        Financing::$finalFinancingStates,
                        true
                    )
                ) {
                    $this->context->controller->warnings[] = $this->l('Order is paid but financing subscription is not in a finished state.');
                }

                $element['buyerFinancedAmountDisplay'] = Tools::displayPrice(
                    $element['buyerFinancedAmount']
                );
                $element['consolidatedStatusDisplay'] = FinancingNamer::getFinancialStateName(
                    $element['consolidatedStatus'],
                    $this,
                    true
                );
                $element['consolidatedSubStatusDisplay'] = FinancingNamer::getFinancialSubStateName(
                    $element['consolidatedSubstatus'],
                    $this
                );
                $element['displayDeliveryConfirmation'] = $this->displayDeliveryConfirmation(
                    $element['solutionCode'],
                    (bool)$element['isDelivered']
                );
                $element['operators'] = SolutionManager::getOperators(
                    $this,
                    SolutionManager::isDeutschLongFinancingSolution($element['solutionCode'])
                );
            }

            $this->context->smarty->assign([
                'financialSubscriptions' => $financialSubscriptions,
            ]);
        }

        $insuranceSubscriptionsByProduct = $this->getOrderInsurances($order);
        if (!empty($insuranceSubscriptionsByProduct)) {
            $this->context->smarty->assign([
                'insuranceSubscriptionsByProduct' => $insuranceSubscriptionsByProduct,
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

        if (Tools::isSubmit('submitSubscriptionConfirmDeliveryRequest')) {
            $creditSubscriptionId = Tools::getValue('creditSubscriptionId');
            $solutionCode = $this->getSolutionCodeForCreditSubscriptionId(
                $creditSubscriptionId,
                $financialSubscriptions
            );

            if (
                empty($solutionCode)
                || (
                    SolutionManager::isFrenchLongFinancingSolution($solutionCode)
                    && empty($trackingNumber)
                )
            ) {
                $this->context->controller->errors[] = $this->l('Financing is not accepted or tracking number is empty.');
            } else {
                $response = Financing::confirmDeliveryFinancingSubscription(
                    $creditSubscriptionId,
                    $trackingNumber,
                    Tools::getValue('operator'),
                    SolutionManager::isFrenchLongFinancingSolution($solutionCode)
                );

                if ($response['hasError']) {
                    if (409 === (int)$response['code']) {
                        $this->context->controller->errors[] = $this->l('The application has been already delivered.');
                    } else {
                        $this->context->controller->errors[] = $this->l('An error occurred during delivery confirmation process.');
                    }
                } else {
                    $this->context->controller->success[] = $this->l('Your delivery confirmation request has been successfully sent.');
                }
            }
        }

        return $this->display(__FILE__, 'views/templates/admin/ps16/order-side.tpl');
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

        $trackingNumber = $this->setTrackingNumberAndOperators($order->getIdOrderCarrier());

        $financialSubscriptions = $this->getOrderSubscriptions($order);
        if (!empty($financialSubscriptions)) {
            foreach ($financialSubscriptions as &$element) {
                $element['buyerFinancedAmountDisplay'] = Tools::displayPrice(
                    $element['buyerFinancedAmount']
                );
                $element['consolidatedStatusDisplay'] = FinancingNamer::getFinancialStateName(
                    $element['consolidatedStatus'],
                    $this,
                    true
                );
                $element['consolidatedSubStatusDisplay'] = FinancingNamer::getFinancialSubStateName(
                    $element['consolidatedSubstatus'],
                    $this
                );
                $element['displayDeliveryConfirmation'] = $this->displayDeliveryConfirmation(
                    $element['solutionCode'],
                    (bool)$element['isDelivered']
                );
                $element['operators'] = SolutionManager::getOperators(
                    $this,
                    SolutionManager::isDeutschLongFinancingSolution($element['solutionCode'])
                );
            }

            $this->context->smarty->assign([
                'financialSubscriptions' => $financialSubscriptions,
            ]);
        }

        $insuranceSubscriptionsByProduct = $this->getOrderInsurances($order);
        if (!empty($insuranceSubscriptionsByProduct)) {
            $this->context->smarty->assign([
                'insuranceSubscriptionsByProduct' => $insuranceSubscriptionsByProduct,
            ]);
        }

        if (Tools::isSubmit('submitSubscriptionCancelRequest')) {
            $response = Financing::cancelFinancingSubscription(
                Tools::getValue('creditSubscriptionId'),
                Tools::getValue('buyerFinancedAmount'),
                Tools::getValue('id_order')
            );

            if ($response['hasError']) {
                $this->get('session')->getFlashBag()->add('error', $this->l('An error occurred during cancellation process.'));
            } else {
                $this->get('session')->getFlashBag()->add('success', $this->l('Your cancel request has been successfully sent.'));
            }

            Tools::redirectAdmin(
                $this->get('router')->generate('admin_orders_view', [
                    'orderId' => (int)Tools::getValue('id_order')
                ])
            );
        }

        if (Tools::isSubmit('submitSubscriptionConfirmDeliveryRequest')) {
            $creditSubscriptionId = Tools::getValue('creditSubscriptionId');
            $solutionCode = $this->getSolutionCodeForCreditSubscriptionId(
                $creditSubscriptionId,
                $financialSubscriptions
            );

            if (
                empty($solutionCode)
                || (
                    SolutionManager::isFrenchLongFinancingSolution($solutionCode)
                    && empty($trackingNumber)
                )
            ) {
                $this->get('session')->getFlashBag()->add(
                    'error',
                    $this->l('Financing is not accepted or tracking number is empty.')
                );
            } else {
                $response = Financing::confirmDeliveryFinancingSubscription(
                    $creditSubscriptionId,
                    $trackingNumber,
                    Tools::getValue('operator', ''),
                    SolutionManager::isFrenchLongFinancingSolution($solutionCode)
                );

                if ($response['hasError']) {
                    if (409 === (int)$response['code']) {
                        $this->get('session')->getFlashBag()->add(
                            'error',
                            $this->l('The application has been already delivered.')
                        );
                    } else {
                        $this->get('session')->getFlashBag()->add(
                            'error',
                            $this->l('An error occurred during delivery confirmation process.')
                        );
                    }
                } else {
                    $this->get('session')->getFlashBag()->add(
                        'success',
                        $this->l('Your delivery confirmation request has been successfully sent.')
                    );
                }
            }

            Tools::redirectAdmin(
                $this->get('router')->generate('admin_orders_view', [
                    'orderId' => (int)Tools::getValue('id_order')
                ])
            );
        }

        return $this->display(__FILE__, 'views/templates/admin/ps17/order-side.tpl');
    }

    public function hookActionOrderStatusPostUpdate($params)
    {
        InsuranceProcess::createOrderInsurancesSubscriptions($params);
    }

    private function getOrderSubscriptions($order)
    {
        $response = Financing::getFinancingSubscriptionsByOrderReference($order->reference);
        return (array_key_exists('hasError', $response) && $response['hasError']) ? [] : $response;
    }

    private function getOrderInsurances($order): array
    {
        $cartInsurances = CartInsurance::getInsuranceByIdCart($order->id_cart);
        if (empty($cartInsurances)) {
            return [];
        }

        $insuranceSubscriptionsByProduct = [];
        foreach ($cartInsurances as $cartInsurance) {
            $insuredProduct = new Product(
                $cartInsurance['id_product'],
                false,
                $this->context->language->id
            );
            $insuranceProduct = new Product(
                $cartInsurance['id_insurance_product'],
                false,
                $this->context->language->id
            );
            $subscriptions = CartInsurance::getInsuranceSubscriptions($cartInsurance['id_cart_insurance']);
            $subscriptionsToAdd = [];

            if (!empty($subscriptions)) {
                foreach ($subscriptions as $subscriptionId) {
                    $apiSubscription = Insurance::getInsuranceSubscriptionBySubscriptionId($subscriptionId);
                    if (empty($apiSubscription)) {
                        continue;
                    }

                    $subscriptionsToAdd[] = [
                        'subscriptionId' => $subscriptionId ?? '',
                        'consolidatedStatus' => $apiSubscription['consolidatedStatus'] ?
                            InsuranceNamer::getInsuranceStateName($apiSubscription['consolidatedStatus'], $this) : '',
                        'duration' => $apiSubscription['duration'] ?? '',
                        'producerQuoteInsurancePrice' => Tools::displayPrice(
                                $apiSubscription['producerQuoteInsurancePrice'],
                                $this->context->currency
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

    public function checkCurrency($cart): bool
    {
        $currency_order = new Currency($cart->id_currency);
        $currencies_module = $this->getCurrency($cart->id_currency);
        if (is_array($currencies_module)) {
            foreach ($currencies_module as $currency_module) {
                if ((int)$currency_order->id === (int)$currency_module['id_currency']) {
                    return true;
                }
            }
        }
        return false;
    }

    private function generateDefaultMapping()
    {
        $mapping = [];
        foreach (Financing::$financingStates as $state) {
            if (in_array($state, Financing::$excludedFinancingStates)) {
                continue;
            }

            $idOrderState = 0;
            if (in_array(
                $state,
                [
                    'INITIALIZED',
                    'PRE_ACCEPTED'
                ]
            )) {
                $idOrderState = Configuration::get(self::ORDER_STATES[0]['configuration']);
            }
            if ('ACCEPTED' === $state) {
                $idOrderState = Configuration::get('PS_OS_PAYMENT');
            }
            if ('REJECTED' === $state) {
                $idOrderState = Configuration::get('PS_OS_ERROR');
            }
            if (in_array(
                $state,
                [
                    'ABORTED',
                    'CANCELLED'
                ]
            )) {
                $idOrderState = Configuration::get('PS_OS_CANCELED');
            }

            $mapping[$state] = $idOrderState;
        }

        return Configuration::updateValue('SCALEXPERT_ORDER_STATE_MAPPING', json_encode($mapping));
    }

    private function displayDeliveryConfirmation($solutionCode, $isDelivered): bool
    {
        return !$isDelivered
            && (
                SolutionManager::isFrenchLongFinancingSolution($solutionCode)
                || SolutionManager::isDeutschLongFinancingSolution($solutionCode)
            );
    }

    private function setTrackingNumberAndOperators($idCarrier)
    {
        $trackingNumber = '';
        $orderCarrier = new OrderCarrier((int)$idCarrier);
        if (Validate::isLoadedObject($orderCarrier)) {
            $trackingNumber = $orderCarrier->tracking_number;
        }

        return $trackingNumber;
    }

    private function getSolutionCodeForCreditSubscriptionId(
        $creditSubscriptionId,
        $financialSubscriptions
    ): string
    {
        $solutionCode = '';

        foreach ($financialSubscriptions as $financialSubscription) {
            // If subscription should not be available for delivery
            if (
                $creditSubscriptionId === $financialSubscription['creditSubscriptionId']
                && true === $financialSubscription['displayDeliveryConfirmation']
            ) {
                $solutionCode = $financialSubscription['solutionCode'];
            }
        }

        return $solutionCode;
    }
}
