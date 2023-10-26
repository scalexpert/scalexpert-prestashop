<?php
/**
 * Copyright © Scalexpert.
 * This file is part of Scalexpert plugin for PrestaShop.
 *
 * @author    Société Générale
 * @copyright Scalexpert
 */

use ScalexpertPlugin\Api\Client;
use ScalexpertPlugin\Api\Financing;
use ScalexpertPlugin\Api\Insurance;
use ScalexpertPlugin\Helper\Hash;

class AdminScalexpertAdministrationController extends ModuleAdminController
{
    /**
     * @var ScalexpertPlugin
     */
    public $module;

    private $_formTabs = [];
    private $_formInputs = [];

    public function __construct()
    {
        $this->bootstrap = true;
        parent::__construct();
    }

    public function postProcess()
    {
        if (Tools::isSubmit('submitAdminScalexpertAdministration')) {
            // Tab Settings
            Configuration::updateValue('SCALEXPERT_ENVIRONMENT', Tools::getValue('mode', 'test'));
            Configuration::updateValue('SCALEXPERT_API_TEST_IDENTIFIER', Tools::getValue('apiTestIdentifier'));
            Configuration::updateValue('SCALEXPERT_API_PRODUCTION_IDENTIFIER', Tools::getValue('apiProductionIdentifier'));

            if ($apiTestKey = Tools::getValue('apiTestKey')) {
                Configuration::updateValue('SCALEXPERT_API_TEST_KEY', Hash::encrypt($apiTestKey));
            }
            if ($apiProductionKey = Tools::getValue('apiProductionKey')) {
                Configuration::updateValue('SCALEXPERT_API_PRODUCTION_KEY', Hash::encrypt($apiProductionKey));
            }

            Configuration::updateValue('SCALEXPERT_DEBUG_MODE', Tools::getValue('debugMode'));

            // Tab Enabled / Disabled
            if ($financingSolutions = Tools::getValue('financingSolutions')) {
                Configuration::updateValue('SCALEXPERT_FINANCING_SOLUTIONS', json_encode($financingSolutions));
            } else {
                Configuration::updateValue('SCALEXPERT_FINANCING_SOLUTIONS', '');
            }
            if ($insuranceSolutions = Tools::getValue('insuranceSolutions')) {
                Configuration::updateValue('SCALEXPERT_INSURANCE_SOLUTIONS', json_encode($insuranceSolutions));
            } else {
                Configuration::updateValue('SCALEXPERT_INSURANCE_SOLUTIONS', '');
            }
            Configuration::updateValue('SCALEXPERT_GROUP_FINANCING_SOLUTIONS', (int)Tools::getValue('groupFinancingSolutions'));

            $this->confirmations[] = $this->_conf[4];
        }

        parent::postProcess();
    }

    public function initContent()
    {
        $this->display = 'view';
        $this->page_header_toolbar_title = $this->toolbar_title = $this->l('Configure the plugin Scalexpert');

        parent::initContent();

        $this->context->smarty->assign([
            'content' => $this->renderForm()
        ]);
    }

    public function renderForm()
    {
        $this->_addTabEnable();
        $this->_addTabDebug();
        $this->_addTabAdmin();

        $this->fields_form = [
            'tabs' => $this->_formTabs,
            'input' => $this->_formInputs,
            'submit' => [
                'title' => $this->l('Save'),
                'name' => 'submitAdminScalexpertAdministration',
                'tab' => 'tabAdmin'
            ]
        ];

        return parent::renderForm();
    }

    private function _setDefaultInput($tabName)
    {
        $this->_formInputs[] = [
            'type' => 'html',
            'name' => 'html_data',
            'html_content' => '<div class="alert alert-warning">' . $this->l('The plugin is not activated, set your keys in the Settings tab') . '</div>',
            'tab' => $tabName
        ];
    }

    private function _addTabEnable()
    {
        $tabName = 'tabEnable';
        $this->_formTabs[$tabName] = $this->l('Enable / Disable');

        if (!$this->_keysExist()) {
            $this->_setDefaultInput($tabName);
            return;
        }

        // Set values
        $financingSolutions = json_decode(Configuration::get('SCALEXPERT_FINANCING_SOLUTIONS'), true);
        if (!empty($financingSolutions)) {
            foreach ($financingSolutions as $key => $value) {
                $this->fields_value['financingSolutions[' . $key . ']'] = $value;
            }
        }

        $insuranceSolutions = json_decode(Configuration::get('SCALEXPERT_INSURANCE_SOLUTIONS'), true);
        if (!empty($insuranceSolutions)) {
            foreach ($insuranceSolutions as $key => $value) {
                $this->fields_value['insuranceSolutions[' . $key . ']'] = $value;
            }
        }

        $this->fields_value['groupFinancingSolutions'] = Configuration::get('SCALEXPERT_GROUP_FINANCING_SOLUTIONS');

        // Set Form Inputs
        $this->_formInputs[] = [
            'type' => 'html',
            'name' => 'html_data',
            'html_content' => '<h2>' . $this->l('Enable/Disable your offers and options') . '</h2><br>' .
                $this->l('Please choose which offers to activate.'),
            'tab' => $tabName
        ];

        // Financing Solutions
        $financingSolutions = Financing::getEligibleSolutions();
        $this->_formInputs[] = [
            'type' => 'html',
            'name' => 'html_data',
            'html_content' => '<h3 class="modal-title">' . $this->l('Financing') . '</h3>' .
                (empty($financingSolutions) ? $this->l('No result for your contract.') : ''),
            'tab' => $tabName
        ];

        $existingSolution = [];
        if (!empty($financingSolutions)) {
            foreach ($financingSolutions as $financingSolution) {
                $existingSolution[] = $financingSolution['solutionCode'];
                $this->_formInputs[] = [
                    'type' => 'switch',
                    'label' => '<img src="'.__PS_BASE_URI__.'modules/'.$this->module->name.'/views/img/flags/'.strtolower($financingSolution['marketCode']).'.jpg" /> '.
                        $this->module->getSolutionDisplayName($financingSolution['solutionCode']),
                    'name' => 'financingSolutions['.$financingSolution['solutionCode'].']',
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
                    'hint' => $financingSolution['communicationKit']['visualAdditionalInformation'],
                    'tab' => $tabName
                ];
            }

        }

        $type = $this->module::TYPES[0];
        $missingSolution = $this->module->getMissingSolution($existingSolution, $type);
        foreach ($missingSolution as $missingSolutionCode) {

            $this->_formInputs[] = [
                'type' => 'switch',
                'label' => '<img src="'.__PS_BASE_URI__.'modules/'.$this->module->name.'/views/img/flags/'.strtolower($this->module->getSolutionFlag($missingSolutionCode)).'.jpg" /> ' .
                    $this->module->getSolutionDisplayName($missingSolutionCode),
                'name' => 'DISABLE_'.$missingSolutionCode,
                'class' => 't',
                'disabled' => true,
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
                'desc' => $this->l('This option is not available in your contract.').'<br/>'.'<a href="'.$this->module->getNewContractUrlByLang($type, $this->context->language->iso_code).'" target="_blank">'.$this->l('Subscribe to this offer.').'</a>',
                'tab' => $tabName
            ];
        }

        if (version_compare(_PS_VERSION_, '1.7', '>=')) {
            $this->_formInputs[] = [
                'type' => 'switch',
                'label' => $this->l('Group all the financing solutions'),
                'name' => 'groupFinancingSolutions',
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

        // Insurance Solutions
        $insuranceSolutions = Insurance::getEligibleSolutions();
        $this->_formInputs[] = [
            'type' => 'html',
            'name' => 'html_data',
            'html_content' => '<h3 class="modal-title">' . $this->l('Insurance') . '</h3>' .
                (empty($insuranceSolutions) ? $this->l('No result for your contract.') : ''),
            'tab' => $tabName
        ];

        $existingSolution = [];
        if (!empty($insuranceSolutions)) {
            foreach ($insuranceSolutions as $insuranceSolution) {
                $existingSolution[] = $insuranceSolution['solutionCode'];

                $this->_formInputs[] = [
                    'type' => 'switch',
                    'label' => '<img src="'.__PS_BASE_URI__.'modules/'.$this->module->name.'/views/img/flags/'.strtolower($this->module->getSolutionFlag($insuranceSolution['solutionCode'])).'.jpg" /> '.$this->module->getSolutionDisplayName($insuranceSolution['solutionCode']),
                    'name' => 'insuranceSolutions['.$insuranceSolution['solutionCode'].']',
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
                    'hint' => $insuranceSolution['communicationKit']['visualAdditionalInformation'],
                    'tab' => $tabName
                ];
            }
        }

        $type = $this->module::TYPES[1];
        $missingSolution = $this->module->getMissingSolution($existingSolution, $type);
        foreach ($missingSolution as $missingSolutionCode) {

            $this->_formInputs[] = [
                'type' => 'switch',
                'label' => '<img src="'.__PS_BASE_URI__.'modules/'.$this->module->name.'/views/img/flags/'.strtolower($this->module->getSolutionFlag($missingSolutionCode)).'.jpg" /> ' .
                    $this->module->getSolutionDisplayName($missingSolutionCode),
                'name' => 'DISABLE_'.$missingSolutionCode,
                'class' => 't',
                'disabled' => true,
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
                'desc' => $this->l('This option is not available in your contract.').'<br/>'.'<a href="'.$this->module->getNewContractUrlByLang($type, $this->context->language->iso_code).'" target="_blank">'.$this->l('Subscribe to this offer.').'</a>',
                'tab' => $tabName
            ];
        }
    }

    private function _addTabDebug()
    {
        $tabName = 'tabDebug';
        $this->_formTabs[$tabName] = $this->l('Debug Mode');

        $this->fields_value['debugMode'] = (int)Configuration::get('SCALEXPERT_DEBUG_MODE');

        if (!$this->_keysExist()) {
            $this->_setDefaultInput($tabName);
        } else {
            $this->_formInputs[] = [
                'type' => 'switch',
                'label' => $this->l('Debug mode'),
                'name' => 'debugMode',
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
                'desc' => $this->l('The log files are available in the folder').' : "/modules/scalexpertplugin/log/"',
                'tab' => $tabName
            ];
        }
    }

    private function _addTabAdmin()
    {
        $tabName = 'tabAdmin';
        $this->_formTabs[$tabName] = $this->l('Settings');

        // Set values
        $this->fields_value['mode'] = Configuration::get('SCALEXPERT_ENVIRONMENT');
        $this->fields_value['apiTestIdentifier'] = Configuration::get('SCALEXPERT_API_TEST_IDENTIFIER');
        $this->fields_value['apiProductionIdentifier'] = Configuration::get('SCALEXPERT_API_PRODUCTION_IDENTIFIER');

        // Set Form Inputs
        $this->_formInputs[] = [
            'type' => 'html',
            'name' => 'html_data',
            'html_content' => '<b>' . $this->l('Configure keys') . '</b><br>' .
                $this->l('Please enter your API key here for the different environments.'),
            'tab' => $tabName
        ];
        $this->_formInputs[] = [
            'label' => $this->l('Choose the environment'),
            'type' => 'select',
            'name' => 'mode',
            'options' => [
                'query' => [
                    [
                        'id' => 'test',
                        'name' => $this->l('Test')
                    ],
                    [
                        'id' => 'production',
                        'name' => $this->l('Production')
                    ]
                ],
                'id' => 'id',
                'name' => 'name'
            ],
            'tab' => $tabName
        ];
        $this->_formInputs[] = [
            'label' => $this->l('Enter your test ID'),
            'type' => 'text',
            'name' => 'apiTestIdentifier',
            'tab' => $tabName
        ];
        $this->_formInputs[] = [
            'label' => $this->l('Enter your test key'),
            'type' => 'textbutton',
            'name' => 'apiTestKey',
            'class' => 'password',
            'desc' => '<a href="https://dev.scalexpert.societegenerale.com/' . $this->context->language->iso_code . '/prod/" target="_blank">' . $this->l('Find my key') . '</a> - ' . $this->l('Leave blank if no change.'),
            'button' => [
                'label' => '<i class="icon-eye"></i>',
                'attributes' => [
                    'onclick' => 'viewPassword(\'apiTestKey\');'
                ]
            ],
            'tab' => $tabName
        ];
        $this->_formInputs[] = [
            'label' => $this->l('Enter your production ID'),
            'type' => 'text',
            'name' => 'apiProductionIdentifier',
            'tab' => $tabName
        ];
        $this->_formInputs[] = [
            'label' => $this->l('Enter your production key'),
            'type' => 'textbutton',
            'name' => 'apiProductionKey',
            'class' => 'password',
            'desc' => '<a href="https://dev.scalexpert.societegenerale.com/' . $this->context->language->iso_code . '/prod/" target="_blank">' . $this->l('Find my key') . '</a> - ' . $this->l('Leave blank if no change.'),
            'button' => [
                'label' => '<i class="icon-eye"></i>',
                'attributes' => [
                    'onclick' => 'viewPassword(\'apiProductionKey\');'
                ]
            ],
            'tab' => $tabName
        ];
        $this->_formInputs[] = [
            'type' => 'html',
            'name' => 'html_data',
            'html_content' => '<button class="btn btn-default" onclick="checkKeys(); return false;">' . $this->l('Check keys') . '</button>',
            'tab' => $tabName
        ];
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

    public function ajaxProcessCheckKeys()
    {
        $client = new Client(Tools::getValue('mode'), Tools::getValue('apiIdentifier'), Tools::getValue('apiKey'));
        $result = $client->getBearer();

        $return = [
            'hasError' => $result['hasError'],
            'error' => ($result['hasError'] ? $result['error'] : false)
        ];

        die(Tools::jsonEncode($return));
    }
}
