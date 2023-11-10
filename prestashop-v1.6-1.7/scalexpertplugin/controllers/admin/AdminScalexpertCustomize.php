<?php
/**
 * Copyright © Scalexpert.
 * This file is part of Scalexpert plugin for PrestaShop.
 *
 * @author    Société Générale
 * @copyright Scalexpert
 */

class AdminScalexpertCustomizeController extends ModuleAdminController
{
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
            /* [
                'id' => 'displayExtraRight',
                'name' => $this->l('HOOK_EXTRA_RIGHT')
            ], */
            [
                'id' => 'displayProductActions',
                'name' => $this->l('Under the add to cart bloc')
            ],
            /* [
                'id' => 'displayProductFooter',
                'name' => $this->l('HOOK_PRODUCT_FOOTER')
            ] */
        ];
    }

    public function postProcess()
    {
        if (\Tools::isSubmit('submitAdminScalexpertCustomize')) {
            \Configuration::updateValue('SCALEXPERT_EXCLUDED_CATEGORIES', json_encode(\Tools::getValue('excludedCategories')));
            \Configuration::updateValue('SCALEXPERT_CUSTOMIZE_PRODUCT', json_encode(\Tools::getValue('customizeProduct')));

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
            //$this->_addTabExcludedCategories();
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

    private function _addTabExcludedCategories()
    {
        $excludedCategories = [];
        if ($jsonExcludedCategories = Configuration::get('SCALEXPERT_EXCLUDED_CATEGORIES')) {
            $excludedCategories = json_decode($jsonExcludedCategories, true);
        }

        $this->_formInputs[] = [
            'type' => 'html',
            'name' => 'html_data',
            'html_content' => '<h2>' . $this->l('Exclude product categories') . '</h2>',
        ];
        $this->_formInputs[] = [
            'type' => 'categories',
            'name' => 'excludedCategories',
            'label' => $this->l('Exclude categories'),
            'tree' => [
                'id' => 'categories-tree',
                'selected_categories' => is_array($excludedCategories) ? $excludedCategories : [],
                'root_category' => \Context::getContext()->shop->id_category,
                'use_search' => true,
                'use_checkbox' => true
            ],
            'desc' => $this->l('By selecting related categories, you are choosing not to display the plugin on products in those categories.')
        ];
    }

    private function _addTabsCustomizeFinancingsDesign()
    {
        $financingSolutions = json_decode(Configuration::get('SCALEXPERT_FINANCING_SOLUTIONS'), true);
        if (empty($financingSolutions)) {
            return;
        }

        if ($jsonCustomizeProduct = Configuration::get('SCALEXPERT_CUSTOMIZE_PRODUCT')) {
            $customizeProduct = json_decode($jsonCustomizeProduct, true);
            foreach ($customizeProduct as $solutionCode => $params) {
                $params = $this->_prepareProductArray($params);
                foreach ($params as $paramKey => $paramValue) {
                    if ('excludedCategories' == $paramKey) {
                        $this->fields_value['customizeProduct['.$solutionCode.']['.$paramKey.'][]'] = $paramValue;
                    } else {
                        $this->fields_value['customizeProduct['.$solutionCode.']['.$paramKey.']'] = $paramValue;
                    }
                }
            }
        }

        foreach ($financingSolutions as $solutionCode => $value) {
            $this->fields_value = $this->_prepareSolutionArray($this->fields_value, $solutionCode);
            $solutionEnabled = ($value ? true : false);
            $tabName = $solutionCode;
            $this->_formTabs[$tabName]
                = ($solutionEnabled ? '<i class="icon-check"></i>' : '<i class="icon-close"></i>') . ' '
                .'<img src="'.__PS_BASE_URI__.'modules/'.$this->module->name.'/views/img/flags/'.strtolower($this->module->getSolutionFlag($solutionCode)).'.jpg" />'. ' '
                . $this->module->getSolutionDisplayName($solutionCode);
            $this->_formInputs[] = [
                'type' => 'html',
                'name' => 'html_data',
                'html_content' => '<p class="alert alert-' . ($solutionEnabled ? 'success' : 'warning') . '">'
                    . ($solutionEnabled ? $this->l('This option is enabled on your site') : $this->l('This option is disabled on your site'))
                    . '<a href="' . $this->context->link->getAdminLink('AdminScalexpertAdministration') . '" style="float: right">' . $this->l('Enable/Disable') . '</a>'
                    . '</p>',
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
                'name' => 'customizeProduct[' . $solutionCode . '][display]',
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
                'label' => $this->l('Position'),
                'type' => 'select',
                'required' => true,
                'name' => 'customizeProduct[' . $solutionCode . '][position]',
                'options' => [
                    'query' => $this->_queryProductHooks,
                    'id' => 'id',
                    'name' => 'name'
                ],
                'tab' => $tabName
            ];
            $this->_formInputs[] = [
                'type' => 'html',
                'name' => 'html_data',
                'html_content' => '<h3 class="modal-title">' . $this->l('Customize the block') . '</h3>',
                'tab' => $tabName
            ];
            $this->_formInputs[] = [
                'label' => $this->l('Title'),
                'type' => 'text',
                // 'lang' => true,
                'name' => 'customizeProduct[' . $solutionCode . '][title]',
                'tab' => $tabName
            ];
            $this->_formInputs[] = [
                'type' => 'switch',
                'label' => $this->l('Display the logo'),
                'name' => 'customizeProduct[' . $solutionCode . '][logo]',
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
            //-----------------------------------
            $this->_formInputs[] = [
                'type' => 'html',
                'name' => 'html_data',
                'html_content' => '<h2>' . $this->l('Payment page') . '</h2>'
                    . '<h3 class="modal-title">' . $this->l('Customize the block') . '</h3>',
                'tab' => $tabName
            ];
            $this->_formInputs[] = [
                'label' => $this->l('Title'),
                'type' => 'text',
                // 'lang' => true,
                'name' => 'customizeProduct[' . $solutionCode . '][title_payment]',
                'tab' => $tabName
            ];
            $this->_formInputs[] = [
                'type' => 'switch',
                'label' => $this->l('Display the logo'),
                'name' => 'customizeProduct[' . $solutionCode . '][logo_payment]',
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

            //-------------------------------------
            // Exclude Categories
            //-------------------------------------
            $this->_formInputs[] = [
                'type' => 'html',
                'name' => 'html_data',
                'html_content' => '<h2>' . $this->l('Exclude product categories') . '</h2>',
                'tab' => $tabName
            ];
            $this->_formInputs[] = [
                'type' => 'select',
                'class' => 'excluded-categories-custom-type',
                'name' => 'customizeProduct[' . $solutionCode . '][excludedCategories]',
                'label' => $this->l('Exclude categories'),
                'options' => [
                    'query' => $this->getCategoriesValue(),
                    'id' => 'id',
                    'name' => 'name'
                ],
                'multiple' => true,
                'tab' => $tabName,
                'desc' => $this->l('By selecting related categories, you are choosing not to display the plugin on products in those categories.').'<br/>'.$this->l('For multi selecting, use CTRL + mouse click on options.')
            ];
        }
    }



    private function _addTabsCustomizeInsurancesDesign()
    {
        $insuranceSolutions = json_decode(Configuration::get('SCALEXPERT_INSURANCE_SOLUTIONS'), true);
        foreach ($insuranceSolutions as $solutionCode => $value) {
            $this->fields_value = $this->_prepareSolutionArray($this->fields_value, $solutionCode);
            $solutionEnabled = ($value ? true : false);
            $tabName = $solutionCode;
            $this->_formTabs[$tabName] = ($solutionEnabled ? '<i class="icon-check"></i>' : '<i class="icon-close"></i>') . ' '.'<img src="'.__PS_BASE_URI__.'modules/'.$this->module->name.'/views/img/flags/'.strtolower($this->module->getSolutionFlag($solutionCode)).'.jpg" />'. ' ' . $this->module->getSolutionDisplayName($solutionCode);
            $this->_formInputs[] = [
                'type' => 'html',
                'name' => 'html_data',
                'html_content' => '<p class="alert alert-' . ($solutionEnabled ? 'success' : 'warning') . '">'
                    . ($solutionEnabled ? $this->l('This option is enabled on your site') : $this->l('This option is disabled on your site'))
                    . '<a href="' . $this->context->link->getAdminLink('AdminScalexpertAdministration') . '" style="float: right">' . $this->l('Enable/Disable') . '</a>'
                    . '</p>',
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
                'name' => 'customizeProduct[' . $solutionCode . '][display]',
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
                'label' => $this->l('Position'),
                'type' => 'select',
                'required' => true,
                'name' => 'customizeProduct[' . $solutionCode . '][position]',
                'options' => [
                    'query' => $this->_queryProductHooks,
                    'id' => 'id',
                    'name' => 'name'
                ],
                'tab' => $tabName
            ];
            $this->_formInputs[] = [
                'type' => 'html',
                'name' => 'html_data',
                'html_content' => '<h3 class="modal-title">' . $this->l('Customize the block') . '</h3>',
                'tab' => $tabName
            ];
            $this->_formInputs[] = [
                'label' => $this->l('Title'),
                'type' => 'text',
                'required' => true,
                // 'lang' => true,
                'name' => 'customizeProduct[' . $solutionCode . '][title]',
                'tab' => $tabName
            ];
            $this->_formInputs[] = [
                'label' => $this->l('Subtitle'),
                'type' => 'text',
                'required' => true,
                // 'lang' => true,
                'name' => 'customizeProduct[' . $solutionCode . '][subtitle]',
                'tab' => $tabName
            ];
            $this->_formInputs[] = [
                'type' => 'switch',
                'label' => $this->l('Display the logo'),
                'name' => 'customizeProduct[' . $solutionCode . '][logo]',
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
                'type' => 'html',
                'name' => 'html_data',
                'html_content' => '<h2>' . $this->l('Cart page') . '</h2>'
                    . '<h3 class="modal-title">' . $this->l('Customize the block') . '</h3>',
                'tab' => $tabName
            ];
            $this->_formInputs[] = [
                'label' => $this->l('Title'),
                'type' => 'text',
                'required' => true,
                // 'lang' => true,
                'name' => 'customizeProduct[' . $solutionCode . '][title_cart]',
                'tab' => $tabName
            ];
            $this->_formInputs[] = [
                'label' => $this->l('Subtitle'),
                'type' => 'text',
                'required' => true,
                // 'lang' => true,
                'name' => 'customizeProduct[' . $solutionCode . '][subtitle_cart]',
                'tab' => $tabName
            ];
            $this->_formInputs[] = [
                'type' => 'switch',
                'label' => $this->l('Display the logo'),
                'name' => 'customizeProduct[' . $solutionCode . '][logo_cart]',
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
                'type' => 'html',
                'name' => 'html_data',
                'html_content' => '<h2>' . $this->l('Exclude product categories') . '</h2>',
                'tab' => $tabName
            ];
            $this->_formInputs[] = [
                'type' => 'select',
                'class' => 'excluded-categories-custom-type',
                'name' => 'customizeProduct[' . $solutionCode . '][excludedCategories]',
                'label' => $this->l('Exclude categories'),
                'options' => [
                    'query' => $this->getCategoriesValue(),
                    'id' => 'id',
                    'name' => 'name'
                ],
                'multiple' => true,
                'tab' => $tabName,
                'desc' => $this->l('By selecting related categories, you are choosing not to display the plugin on products in those categories.').'<br/>'.$this->l('For multi selecting, use CTRL + mouse click on options.')
            ];
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
                $new .= implode(" > ", $parents).' > ';
            }
            $result[] = [
                'id' => $element['id_category'],
                'name' => $new.$element['name']
            ];
            if (isset($element['children'])) {
                $results = $this->loopCategories(
                    $element['children'],
                    array_merge($parents, [$element['name']])
                );
                $result = array_merge($result, $results);
            }
        }

        return $result;
    }

    private function _keysExist()
    {
        if ((Configuration::get('SCALEXPERT_API_TEST_IDENTIFIER') && Configuration::get('SCALEXPERT_API_TEST_KEY'))
            || (Configuration::get('SCALEXPERT_API_PRODUCTION_IDENTIFIER') && Configuration::get('SCALEXPERT_API_PRODUCTION_KEY'))
        ) {
            return true;
        }

        return false;
    }

    private function _setDefaultInput()
    {
        $this->_formInputs[] = [
            'type' => 'html',
            'name' => 'html_data',
            'html_content' => '<div class="alert alert-warning">' . $this->l('The plugin is not activated, set your keys in the Settings tab') . '</div>',
        ];
    }

    private function _prepareProductArray($array)
    {
        return array_merge([
            'display' => '',
            'position' => '',
            'title' => '',
            'subtitle' => '',
            'logo' => '',
            'excludedCategories' => [],
        ], $array);
    }

    private function _prepareSolutionArray($array, $solutionCode)
    {
        if (!is_array($array)) {
            $array = [];
        }

        return array_merge([
            'customizeProduct[' . $solutionCode . '][excludedCategories][]' => [],
        ], $array);
    }
}
