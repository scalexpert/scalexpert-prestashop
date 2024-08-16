<?php
/**
 * Copyright © Scalexpert.
 * This file is part of Scalexpert plugin for PrestaShop. See COPYING.md for license details.
 *
 * @author    Scalexpert (https://scalexpert.societegenerale.com/)
 * @copyright Scalexpert
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */


use ScalexpertPlugin\Api\Client;
use ScalexpertPlugin\Api\Financing;
use ScalexpertPlugin\Api\Insurance;
use ScalexpertPlugin\Helper\FinancingNamer;
use ScalexpertPlugin\Helper\Hash;
use ScalexpertPlugin\Helper\SolutionManager;

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
            $config = [
                'SCALEXPERT_ENVIRONMENT' => Tools::getValue('mode', 'test'),
                'SCALEXPERT_API_TEST_IDENTIFIER' => Tools::getValue('apiTestIdentifier'),
                'SCALEXPERT_API_PRODUCTION_IDENTIFIER' => Tools::getValue('apiProductionIdentifier'),
                'SCALEXPERT_DEBUG_MODE' => Tools::getValue('debugMode'),
                'SCALEXPERT_FINANCING_SOLUTIONS', '',
                'SCALEXPERT_INSURANCE_SOLUTIONS', '',
                'SCALEXPERT_GROUP_FINANCING_SOLUTIONS' => (int)Tools::getValue('groupFinancingSolutions'),
            ];

            // Tab Settings
            if ($apiTestKey = Tools::getValue('apiTestKey')) {
                $config['SCALEXPERT_API_TEST_KEY'] = Hash::encrypt($apiTestKey);
            }
            if ($apiProductionKey = Tools::getValue('apiProductionKey')) {
                $config['SCALEXPERT_API_PRODUCTION_KEY'] = Hash::encrypt($apiProductionKey);
            }

            // Tab Enabled / Disabled
            if ($financingSolutions = Tools::getValue('financingSolutions')) {
                $config['SCALEXPERT_FINANCING_SOLUTIONS'] = json_encode($financingSolutions);
            }
            if ($insuranceSolutions = Tools::getValue('insuranceSolutions')) {
                $config['SCALEXPERT_INSURANCE_SOLUTIONS'] = json_encode($insuranceSolutions);
            }

            // Tab Order State Mapping
            if ($orderStateMapping = Tools::getValue('orderStateMapping')) {
                $config['SCALEXPERT_ORDER_STATE_MAPPING'] = json_encode($orderStateMapping);
            }

            foreach ($config as $k => $v) {
                Configuration::updateValue($k, $v);
            }

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
        $this->_addTabOrderStateMapping();

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
        $this->_setConfigValueToFieldsValue('SCALEXPERT_FINANCING_SOLUTIONS', 'financingSolutions');
        $this->_setConfigValueToFieldsValue('SCALEXPERT_INSURANCE_SOLUTIONS', 'insuranceSolutions');
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
                    'label' => $this->getImgForSolutionCode(
                        $financingSolution['marketCode'],
                        $financingSolution['solutionCode']
                    ),
                    'name' => 'financingSolutions[' . $financingSolution['solutionCode'] . ']',
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

        $this->_addMissingSolutionsByType(SolutionManager::TYPES[0], $existingSolution, $tabName);

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
                    'label' => $this->getImgForSolutionCode(
                        SolutionManager::getSolutionFlag($insuranceSolution['solutionCode']),
                        $insuranceSolution['solutionCode']
                    ),
                    'name' => 'insuranceSolutions[' . $insuranceSolution['solutionCode'] . ']',
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

        $this->_addMissingSolutionsByType(SolutionManager::TYPES[1], $existingSolution, $tabName);
    }

    private function _addMissingSolutionsByType($type, $existingSolution, $tabName)
    {
        $missingSolution = SolutionManager::getMissingSolution($existingSolution, $type);

        foreach ($missingSolution as $missingSolutionCode) {
            $this->_formInputs[] = [
                'type' => 'switch',
                'label' => $this->getImgForSolutionCode(
                    SolutionManager::getSolutionFlag($missingSolutionCode),
                    $missingSolutionCode
                ),
                'name' => 'DISABLE_' . $missingSolutionCode,
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
                'desc' => $this->l('This option is not available in your contract.') . '<br/>' . '<a href="' . SolutionManager::getNewContractUrlByLang($type, $this->context->language->iso_code) . '" target="_blank">' . $this->l('Subscribe to this offer.') . '</a>',
                'tab' => $tabName
            ];
        }
    }

    private function _setConfigValueToFieldsValue($configName, $fieldIndex)
    {
        $values = json_decode(Configuration::get($configName), true);
        if (!empty($values)) {
            foreach ($values as $key => $value) {
                $this->fields_value[$fieldIndex . '[' . $key . ']'] = $value;
            }
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
                'desc' => $this->l('The log files are available in the folder') . ' : "/modules/scalexpertplugin/log/"',
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
        $this->fields_value['apiTestKey'] = Hash::decrypt(Configuration::get('SCALEXPERT_API_TEST_KEY'));
        $this->fields_value['apiProductionIdentifier'] = Configuration::get('SCALEXPERT_API_PRODUCTION_IDENTIFIER');
        $this->fields_value['apiProductionKey'] = Hash::decrypt(Configuration::get('SCALEXPERT_API_PRODUCTION_KEY'));

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

    private function _addTabOrderStateMapping()
    {
        $tabName = 'tabOrderStateMapping';
        $this->_formTabs[$tabName] = $this->l('Order state mapping');

        if (!$this->_keysExist()) {
            $this->_setDefaultInput($tabName);
            return;
        }

        // Set values
        $orderStateMapping = json_decode(Configuration::get('SCALEXPERT_ORDER_STATE_MAPPING'), true);
        if (!empty($orderStateMapping)) {
            foreach ($orderStateMapping as $key => $value) {
                $this->fields_value['orderStateMapping[' . $key . ']'] = $value;
            }
        }

        $orderStates = \OrderState::getOrderStates(Context::getContext()->language->id);
        $orderStatesChoices[] = [
            'name' => $this->l('No status'),
            'id_state' => 0
        ];
        if (!empty($orderStates)) {
            foreach ($orderStates as $orderState) {
                $orderStatesChoices[] = [
                    'name' => $orderState['name'],
                    'id_state' => $orderState['id_order_state']
                ];
            }
        }

        foreach (Financing::$financingStates as $state) {
            if (in_array($state, Financing::$excludedFinancingStates)) {
                continue;
            }

            $this->_formInputs[] = [
                'label' => FinancingNamer::getFinancialStateName($state, $this->module, true, true),
                'type' => 'select',
                'name' => 'orderStateMapping[' . $state . ']',
                'options' => [
                    'query' => $orderStatesChoices,
                    'id' => 'id_state',
                    'name' => 'name',
                ],
                'tab' => $tabName
            ];
        }

        $infoUrl = 'https://docs.scalexpert.societegenerale.com/apidocs/3mLlrPx3sPtekcQvEEUg/for-discovery/credit/e-financing-status-life-cycle';
        $this->_formInputs[] = [
            'type' => 'html',
            'name' => 'html_data',
            'html_content' => $this->l('For more information regarding e-financing status, please clic on the following link') .
                '<br><a href="' . $infoUrl . '">' . $infoUrl . '</a>',
            'tab' => $tabName
        ];

        $this->_formInputs[] = [
            'type' => 'html',
            'name' => 'html_data',
            'html_content' => '<b>' . $this->l('Cron task') . '</b><br>' .
                $this->l('You configure this cron task (every hour) on your server : ') .
                '<br>0 * * * * <a href="#">' . $this->context->link->getModuleLink($this->module->name, 'maintenance', [], true) . '</a>',
            'tab' => $tabName
        ];
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

    public function ajaxProcessCheckKeys()
    {
        $client = new Client(Tools::getValue('mode'), Tools::getValue('apiIdentifier'), Tools::getValue('apiKey'));
        $result = $client->getBearer();

        $return = [
            'hasError' => $result['hasError'],
            'error' => ($result['hasError'] ? $result['error'] : false)
        ];

        $this->ajaxDie(Tools::jsonEncode($return));
    }

    private function getImgForSolutionCode($imgName, $solutionCode): string
    {
        return '<img src="' . __PS_BASE_URI__ . 'modules/' . $this->module->name . '/views/img/flags/' .
            strtolower($imgName) . '.jpg" /> ' .
            SolutionManager::getSolutionDisplayName($solutionCode, $this->module);
    }
}
