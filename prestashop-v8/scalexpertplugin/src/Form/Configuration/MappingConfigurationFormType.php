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

namespace ScalexpertPlugin\Form\Configuration;

use PrestaShopBundle\Form\Admin\Type\TranslatorAwareType;
use ScalexpertPlugin\Handler\SolutionNameHandler;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Translation\TranslatorInterface;

class MappingConfigurationFormType extends TranslatorAwareType
{
    /**
     * @var SolutionNameHandler
     */
    private $solutionNameHandler;
    public function __construct(
        TranslatorInterface $translator,
        array $locales,
        SolutionNameHandler $solutionNameHandler
    )
    {
        parent::__construct($translator, $locales);
        $this->solutionNameHandler = $solutionNameHandler;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $financialStates = MappingConfigurationFormDataConfiguration::FINANCING_STATES;
        if (!empty($financialStates)) {
            foreach ($financialStates as $financialState) {
                if (in_array(
                        $financialState,
                        MappingConfigurationFormDataConfiguration::EXCLUDED_FINANCING_STATES
                    )
                ) {
                    continue;
                }

                $builder->add($financialState, ChoiceType::class, [
                    'label' => $this->solutionNameHandler->getFinancialStateName(
                        $financialState,
                        $this->getTranslator(),
                        true,
                        true
                    ),
                    'choices' => $this->getChoices(),
                ]);
            }
        }
    }

    private function getChoices() {
        $states[$this->trans('No status', 'Modules.Scalexpertplugin.Admin')] = 0;
        $orderStates = \OrderState::getOrderStates(\Context::getContext()->language->id);
        if (!empty($orderStates)) {
            foreach ($orderStates as $orderState) {
                $states[$orderState['name']] = $orderState['id_order_state'];
            }
        }

        return $states;
    }
}
