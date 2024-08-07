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

use PrestaShopBundle\Form\Admin\Type\SwitchType;
use PrestaShopBundle\Form\Admin\Type\TranslatorAwareType;
use ScalexpertPlugin\Handler\SolutionNameHandler;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Translation\TranslatorInterface;

class FinancingConfigurationFormType extends TranslatorAwareType
{
    private $apiClient;

    private $solutionNameHandler;

    public function __construct(
        TranslatorInterface $translator,
        array $locales,
        $apiClient,
        $solutionNameHandler
    )
    {
        $this->apiClient = $apiClient;
        $this->solutionNameHandler = $solutionNameHandler;

        parent::__construct($translator, $locales);
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $financialSolutions = $this->apiClient->getAllFinancialSolutions();

        $existingSolution = [];
        if (!empty($financialSolutions)) {
            foreach ($financialSolutions as $solutionCode => &$financialSolution) {
                $existingSolution[] = $solutionCode;
                $solutionName = $this->solutionNameHandler->getSolutionName($solutionCode);

                if (!empty($solutionName)) {
                    $financialSolution['visualTitle'] = $solutionName;
                }

                $flagImage = '<img src="' . $financialSolution['countryFlag'] . '" alt="' . $financialSolution['buyerBillingCountry'] . '">';
                $builder->add($financialSolution['solutionCode'], SwitchType::class, [
                    'label' => $flagImage . ' ' . $financialSolution['visualTitle'],
                    'choices' => [
                        $this->trans('Disabled', 'Modules.Scalexpertplugin.Admin') => false,
                        $this->trans('Enabled', 'Modules.Scalexpertplugin.Admin') => true,
                    ],
                    'default_empty_data' => false,
                ]);
            }
        }

        /** @var SolutionNameHandler $solutionNameHandler */
        $missingSolution = $this->solutionNameHandler->getMissingSolution($existingSolution, 'financials');
        foreach ($missingSolution as $missingSolutionCode) {
            $flagImage = '<img src="' . sprintf('/img/flags/%s.jpg', strtolower($this->solutionNameHandler->getSolutionFlag($missingSolutionCode))) . '" alt="' . $this->solutionNameHandler->getSolutionFlag($missingSolutionCode) . '">';
            $builder->add('DISABLE_'.$missingSolutionCode, SwitchType::class, [
                'label' => $flagImage . ' ' . $this->solutionNameHandler->getSolutionName($missingSolutionCode),
                'choices' => [
                    $this->trans('Disabled', 'Modules.Scalexpertplugin.Admin') => false,
                    $this->trans('Enabled', 'Modules.Scalexpertplugin.Admin') => true,
                ],
                'disabled' => true,
                'default_empty_data' => false,
                'help' => $this->trans('This option is not available in your contract.', 'Modules.Scalexpertplugin.Admin'),
            ]);
        }
    }
}
