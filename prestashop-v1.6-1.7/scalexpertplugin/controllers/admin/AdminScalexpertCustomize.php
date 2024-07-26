<?php
/**
 * Copyright © Scalexpert.
 * This file is part of Scalexpert plugin for PrestaShop. See COPYING.md for license details.
 *
 * @author    Scalexpert (https://scalexpert.societegenerale.com/)
 * @copyright Scalexpert
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */


use ScalexpertPlugin\Helper\SolutionManager;

class AdminScalexpertCustomizeController extends ModuleAdminController
{
    const CUSTOMIZE_PRODUCT_INDEX = 'customizeProduct';

    /**
     * @var ScalexpertPlugin
     */
    public $module;

    private $_formTabs = [];
    private $_formInputs = [];
    private $_queryProductHooks;

    public function __construct()
    {
        $this->bootstrap = true;
        parent::__construct();

        $this->_queryProductHooks = [
            [
                'id' => 'displayProductActions',
                'name' => $this->l('Under the add to cart bloc')
            ],
        ];
    }

    public function postProcess()
    {
        if (\Tools::isSubmit('submitAdminScalexpertCustomize')) {
            $formValues = \Tools::getValue(static::CUSTOMIZE_PRODUCT_INDEX);
            foreach ($formValues as $k => $value) {
                if (array_key_exists('excludedCategories', $value)) {
                    continue;
                }

                $formValues[$k]['excludedCategories'] = [];
            }

            \Configuration::updateValue('SCALEXPERT_CUSTOMIZE_PRODUCT', json_encode($formValues));

            $this->confirmations[] = $this->_conf[4];
        }

        parent::postProcess();
    }

    public function initContent()
    {
        $this->display = 'view';
        $this->page_header_toolbar_title = $this->toolbar_title = $this->l('Customize the plugin Scalexpert');

        parent::initContent();

        $this->context->smarty->assign([
            'content' => $this->renderForm()
        ]);
    }

    public function renderForm()
    {
        if (!$this->_keysExist()) {
            $this->_setDefaultInput();

            $this->fields_form = [
                'input' => $this->_formInputs,
                'submit' => [
                    'title' => $this->l('Save'),
                    'name' => 'submitAdminScalexpertCustomize',
                ]
            ];
        } else {
            $this->_addTabsCustomizeFinancingsDesign();
            $this->_addTabsCustomizeInsurancesDesign();

            $this->fields_form = [
                'tabs' => $this->_formTabs,
                'input' => $this->_formInputs,
                'submit' => [
                    'title' => $this->l('Save'),
                    'name' => 'submitAdminScalexpertCustomize',
                ]
            ];
        }

        return parent::renderForm();
    }

    /**
     * @throws PrestaShopException
     */
    private function _addTabsCustomizeFinancingsDesign()
    {
        $financingSolutions = json_decode(Configuration::get('SCALEXPERT_FINANCING_SOLUTIONS'), true);
        if (empty($financingSolutions)) {
            return;
        }

        $this->_setCustomizeProductValueToFieldsValue();

        foreach ($financingSolutions as $solutionCode => $value) {
            $this->fields_value = $this->_prepareSolutionArray($this->fields_value, $solutionCode);
            $solutionEnabled = (bool)$value;
            $tabName = $solutionCode;
            $this->_getSolutionFields($tabName, $solutionCode, $solutionEnabled);
            $this->_formInputs[] = [
                'label' => $this->l('Title'),
                'type' => 'text',
                'name' => static::CUSTOMIZE_PRODUCT_INDEX . '[' . $solutionCode . '][title]',
                'tab' => $tabName
            ];
            $this->_addLogoField(
                static::CUSTOMIZE_PRODUCT_INDEX . '[' . $solutionCode . '][logo]',
                $tabName
            );

            //-----------------------------------
            $this->_addCustomizeTitleField($tabName, true, false);
            $this->_formInputs[] = [
                'label' => $this->l('Title'),
                'type' => 'text',
                'name' => static::CUSTOMIZE_PRODUCT_INDEX . '[' . $solutionCode . '][title_payment]',
                'tab' => $tabName
            ];
            $this->_addLogoField(
                static::CUSTOMIZE_PRODUCT_INDEX . '[' . $solutionCode . '][logo_payment]',
                $tabName
            );
            $this->_formInputs[] = [
                'label' => $this->l('Position'),
                'type' => 'text',
                'required' => true,
                'name' => static::CUSTOMIZE_PRODUCT_INDEX . '[' . $solutionCode . '][position]',
                'tab' => $tabName
            ];

            $this->_addExcludedProductField($solutionCode, $tabName);
            $this->_addExcludedCategoryField($solutionCode, $tabName);
        }
    }

    private function _setCustomizeProductValueToFieldsValue()
    {
        $jsonCustomizeProduct = Configuration::get('SCALEXPERT_CUSTOMIZE_PRODUCT');
        if (!empty($jsonCustomizeProduct)) {
            $customizeProduct = json_decode($jsonCustomizeProduct, true);
            foreach ($customizeProduct as $solutionCode => $params) {
                $params = $this->_prepareProductArray($params);
                foreach ($params as $paramKey => $paramValue) {
                    $index = static::CUSTOMIZE_PRODUCT_INDEX . '[' . $solutionCode . '][' . $paramKey . ']';
                    if ('excludedCategories' === $paramKey) {
                        $index = static::CUSTOMIZE_PRODUCT_INDEX . '[' . $solutionCode . '][' . $paramKey . '][]';
                    }

                    $this->fields_value[$index] = $paramValue;
                }
            }
        }
    }

    private function _addTabsCustomizeInsurancesDesign()
    {
        $insuranceSolutions = json_decode(Configuration::get('SCALEXPERT_INSURANCE_SOLUTIONS'), true);
        if (empty($insuranceSolutions)) {
            return;
        }

        foreach ($insuranceSolutions as $solutionCode => $value) {
            $this->fields_value = $this->_prepareSolutionArray($this->fields_value, $solutionCode);

            $solutionEnabled = (bool)$value;
            $tabName = $solutionCode;
            $this->_getSolutionFields($tabName, $solutionCode, $solutionEnabled);
            $this->_formInputs[] = [
                'label' => $this->l('Title'),
                'type' => 'text',
                'required' => true,
                'name' => static::CUSTOMIZE_PRODUCT_INDEX . '[' . $solutionCode . '][title]',
                'tab' => $tabName
            ];
            $this->_formInputs[] = [
                'label' => $this->l('Subtitle'),
                'type' => 'text',
                'required' => true,
                'name' => static::CUSTOMIZE_PRODUCT_INDEX . '[' . $solutionCode . '][subtitle]',
                'tab' => $tabName
            ];
            $this->_addLogoField(
                static::CUSTOMIZE_PRODUCT_INDEX . '[' . $solutionCode . '][logo]',
                $tabName
            );

            $this->_addCustomizeTitleField($tabName, false, true);
            $this->_formInputs[] = [
                'label' => $this->l('Title'),
                'type' => 'text',
                'required' => true,
                'name' => static::CUSTOMIZE_PRODUCT_INDEX . '[' . $solutionCode . '][title_cart]',
                'tab' => $tabName
            ];
            $this->_addLogoField(
                static::CUSTOMIZE_PRODUCT_INDEX . '[' . $solutionCode . '][logo_cart]', $tabName
            );
            $this->_addExcludedProductField($solutionCode, $tabName);
            $this->_addExcludedCategoryField($solutionCode, $tabName);
        }
    }

    private function getCategoriesValue()
    {
        $idRootCategory = (int)Configuration::get('PS_ROOT_CATEGORY');
        $categories = Category::getNestedCategories(
            $idRootCategory,
            $this->context->language->id,
            false
        );

        if (!isset($categories[$idRootCategory]['children'])) {
            return [];
        }

        return $this->loopCategories($categories[$idRootCategory]['children']);
    }

    private function loopCategories($data, $parents = [])
    {
        $result = [];

        foreach ($data as $element) {
            $new = '';
            if (count($parents)) {
                $new .= implode(" > ", $parents) . ' > ';
            }
            $result[] = [
                'id' => $element['id_category'],
                'name' => $new . $element['name']
            ];
            if (isset($element['children'])) {
                $results = $this->loopCategories(
                    $element['children'],
                    array_merge($parents, [$element['name']])
                );
                array_push($result, ...$results);
            }
        }

        return $result;
    }

    private function _keysExist()
    {
        return (
                Configuration::get('SCALEXPERT_API_TEST_IDENTIFIER')
                && Configuration::get('SCALEXPERT_API_TEST_KEY')
            ) || (
                Configuration::get('SCALEXPERT_API_PRODUCTION_IDENTIFIER')
                && Configuration::get('SCALEXPERT_API_PRODUCTION_KEY')
            );
    }

    private function _setDefaultInput()
    {
        $this->_formInputs[] = [
            'type' => 'html',
            'name' => 'html_data',
            'html_content' => '<div class="alert alert-warning">' . $this->l('The plugin is not activated, set your keys in the Settings tab') . '</div>',
        ];
    }

    private function _prepareProductArray($array): array
    {
        return array_merge([
            'display' => '',
            'hook' => '',
            'position' => 0,
            'title' => '',
            'subtitle' => '',
            'logo' => '',
            'excludedCategories' => [],
            'excludedProducts' => '',
        ], $array);
    }

    private function _prepareSolutionArray($array, $solutionCode)
    {
        if (!is_array($array)) {
            $array = [];
        }

        return array_merge([
            static::CUSTOMIZE_PRODUCT_INDEX . '[' . $solutionCode . '][excludedCategories][]' => [],
            static::CUSTOMIZE_PRODUCT_INDEX . '[' . $solutionCode . '][excludedProducts]' => '',
        ], $array);
    }

    private function _addExcludedProductField($solutionCode, $tabName)
    {
        $implodedExcludedProducts = $this->fields_value[static::CUSTOMIZE_PRODUCT_INDEX . '[' . $solutionCode . '][excludedProducts]'];
        $explodedExcludedProducts = !empty($implodedExcludedProducts) ? explode(',', $implodedExcludedProducts) : [];
        $excludedProductNames = '';
        $excludedProductBtns = '';
        foreach ($explodedExcludedProducts as $accessory) {
            if (empty($accessory)) {
                continue;
            }
            $product = new Product((int)$accessory, false, $this->context->language->id);
            $excludedProductNames .= $product->name . ' (ref:' . $product->reference . ')¤';
            $excludedProductBtns .= '
                <div class="form-control-static">
                <button type="button" class="btn btn-default delAccessory" name="' . $accessory . '">
                    <i class="icon-remove text-danger"></i>
                </button>
                ' . $product->name . ' (ref:' . $product->reference . ')
            </div>';
        }

        $html = '<input type="hidden" name="' . static::CUSTOMIZE_PRODUCT_INDEX . '[' . $solutionCode . '][excludedProducts]" id="inputAccessories" class="inputAccessories" value="' . $implodedExcludedProducts . '" />
            <input type="hidden" name="nameAccessories" id="nameAccessories" class="nameAccessories" value="' . $excludedProductNames . '" />
            <div id="ajax_choose_product">
                <div class="input-group">
                    <input type="text" id="product_autocomplete_input" name="product_autocomplete_input" placeholder="' . $this->l('Find product by name or SKU') . '" />
                    <span class="input-group-addon"><i class="icon-search"></i></span>
                </div>
            </div>
            <div id="divAccessories" class="divAccessories">' . $excludedProductBtns . '</div>';

        $this->_formInputs[] = [
            'type' => 'html',
            'name' => 'html_data',
            'html_content' => '<h2>' . $this->l('Exclude products') . '</h2>',
            'tab' => $tabName
        ];
        $this->_formInputs[] = [
            'type' => 'html',
            'name' => 'html_data',
            'html_content' => $html,
            'tab' => $tabName
        ];
    }

    private function _addExcludedCategoryField($solutionCode, $tabName)
    {
        $this->_formInputs[] = [
            'type' => 'html',
            'name' => 'html_data',
            'html_content' => '<h2>' . $this->l('Exclude product categories') . '</h2>',
            'tab' => $tabName
        ];
        $this->_formInputs[] = [
            'type' => 'select',
            'class' => 'excluded-categories-custom-type',
            'name' => static::CUSTOMIZE_PRODUCT_INDEX . '[' . $solutionCode . '][excludedCategories]',
            'label' => $this->l('Exclude categories'),
            'options' => [
                'query' => $this->getCategoriesValue(),
                'id' => 'id',
                'name' => 'name'
            ],
            'multiple' => true,
            'tab' => $tabName,
            'desc' => $this->l('By selecting related categories, you are choosing not to display the plugin on products in those categories.')
                . '<br/>' . $this->l('For multi selecting, use CTRL + mouse click on options.')
        ];
    }

    /**
     * @param $tabName
     * @param $solutionCode
     * @param $solutionEnabled
     * @throws PrestaShopException
     */
    private function _getSolutionFields($tabName, $solutionCode, $solutionEnabled)
    {
        $this->_formTabs[$tabName] = ($solutionEnabled ? '<i class="icon-check"></i>' : '<i class="icon-close"></i>')
            . ' ' . '<img src="' . __PS_BASE_URI__ . 'modules/' . $this->module->name . '/views/img/flags/'
            . strtolower(SolutionManager::getSolutionFlag($solutionCode)) . '.jpg" />' . ' '
            . SolutionManager::getSolutionDisplayName($solutionCode, $this->module);
        $this->_formInputs[] = [
            'type' => 'html',
            'name' => 'html_data',
            'html_content' => '<p class="alert alert-' . ($solutionEnabled ? 'success' : 'warning') . '">'
                . ($solutionEnabled ?
                    $this->l('This option is enabled on your site')
                    : $this->l('This option is disabled on your site')
                ) . '<a href="' . $this->context->link->getAdminLink('AdminScalexpertAdministration')
                . '" style="float: right">' . $this->l('Enable/Disable') . '</a>' . '</p>',
            'tab' => $tabName
        ];
        $this->_formInputs[] = [
            'type' => 'html',
            'name' => 'html_data',
            'html_content' => '<h2>' . $this->l('Product page') . '</h2>'
                . '<h3 class="modal-title">' . $this->l('Display the block') . '</h3>',
            'tab' => $tabName
        ];
        $this->_formInputs[] = [
            'type' => 'switch',
            'label' => $this->l('Display on the product page'),
            'name' => static::CUSTOMIZE_PRODUCT_INDEX . '[' . $solutionCode . '][display]',
            'class' => 't',
            'is_bool' => true,
            'values' => [
                [
                    'value' => 1,
                    'label' => $this->l('Enabled')
                ],
                [
                    'value' => 0,
                    'label' => $this->l('Disabled')
                ]
            ],
            'tab' => $tabName
        ];
        $this->_formInputs[] = [
            'label' => $this->l('Hook'),
            'type' => 'select',
            'required' => true,
            'name' => static::CUSTOMIZE_PRODUCT_INDEX . '[' . $solutionCode . '][hook]',
            'options' => [
                'query' => $this->_queryProductHooks,
                'id' => 'id',
                'name' => 'name'
            ],
            'tab' => $tabName
        ];
        $this->_addCustomizeTitleField($tabName, false, false);
    }

    private function _addLogoField($name, $tabName)
    {
        $this->_formInputs[] = [
            'type' => 'switch',
            'label' => $this->l('Display the logo'),
            'name' => $name,
            'class' => 't',
            'is_bool' => true,
            'values' => [
                [
                    'value' => 1,
                    'label' => $this->l('Enabled')
                ],
                [
                    'value' => 0,
                    'label' => $this->l('Disabled')
                ]
            ],
            'tab' => $tabName
        ];
    }

    private function _addCustomizeTitleField($tabName, $isPaymentPageTitle, $isCartPageTitle)
    {
        $htmlTitle = '';
        if ($isPaymentPageTitle) {
            $htmlTitle = '<h2>' . $this->l('Payment page') . '</h2>';
        }
        if ($isCartPageTitle) {
            $htmlTitle = '<h2>' . $this->l('Cart page') . '</h2>';
        }

        $this->_formInputs[] = [
            'type' => 'html',
            'name' => 'html_data',
            'html_content' => $htmlTitle . '<h3 class="modal-title">' . $this->l('Customize the block') . '</h3>',
            'tab' => $tabName
        ];
    }
}
