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

namespace ScalexpertPlugin\Form\Customize;

use PrestaShop\PrestaShop\Adapter\LegacyContext;
use PrestaShopBundle\Form\Admin\Type\CategoryChoiceTreeType;
use PrestaShopBundle\Form\Admin\Type\SwitchType;
use PrestaShopBundle\Form\Admin\Type\TranslatorAwareType;
use PrestaShopBundle\Form\Admin\Type\TypeaheadProductCollectionType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Translation\TranslatorInterface;

class DesignCustomizeFormType extends TranslatorAwareType
{
    private $translator;
    private $apiClient;
    private $context;

    public function __construct(
        TranslatorInterface $translator,
        array               $locales,
                            $apiClient,
        LegacyContext       $legacyContext
    )
    {
        $this->translator = $translator;
        $this->context = $legacyContext;
        $this->apiClient = $apiClient;

        parent::__construct($translator, $locales);
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $financialSolutions = $this->apiClient->getAllFinancialSolutions();
        $insuranceSolutions = $this->apiClient->getAllInsuranceSolutions();
        $allSolutions = array_merge($financialSolutions, $insuranceSolutions);

        if (!empty($allSolutions)) {
            foreach ($allSolutions as $solution) {
                $builder
                    ->add($solution['solutionCode'] . DesignCustomizeFormDataConfiguration::ID_DELIMITER . 'excludedCategories', CategoryChoiceTreeType::class, [
                        'label' => $this->trans('Excluded categories', 'Modules.Scalexpertplugin.Admin'),
                        'multiple' => true,
                        'help' => $this->trans('By selecting related categories, you are choosing not to display the plugin on products in those categories.', 'Modules.Scalexpertplugin.Admin')
                    ])
                    ->add($solution['solutionCode'] . DesignCustomizeFormDataConfiguration::ID_DELIMITER . 'excludedProducts', TypeaheadProductCollectionType::class, [
                        'remote_url' => $this->context->getLegacyAdminLink('AdminProducts', true, ['ajax' => 1, 'action' => 'productsList', 'forceJson' => 1, 'disableCombination' => 1, 'exclude_packs' => 0, 'excludeVirtuals' => 1, 'limit' => 20]) . '&q=%QUERY',
                        'mapping_value' => 'id',
                        'mapping_name' => 'name',
                        'placeholder' => $this->translator->trans('Search and add an excluded product', [], 'Admin.Catalog.Help'),
                        'template_collection' => '<span class="label">%s</span><i class="material-icons delete">clear</i>',
                        'required' => false,
                        'label' => $this->translator->trans('Excluded products', [], 'Modules.Scalexpertplugin.Admin'),
                        'help' => $this->trans('By selecting excluded products, you are choosing not to display the plugin on those products.', 'Modules.Scalexpertplugin.Admin')
                    ])
                    ->add($solution['solutionCode'] . DesignCustomizeFormDataConfiguration::ID_DELIMITER . 'position', TextType::class, [
                        'label' => $this->trans('Position', 'Modules.Scalexpertplugin.Admin'),
                        'default_empty_data' => 0,
                        'help' => $this->trans('Set the order in which solutions are displayed.', 'Modules.Scalexpertplugin.Admin')
                    ])
                    ->add($solution['solutionCode'] . DesignCustomizeFormDataConfiguration::ID_DELIMITER . 'productDisplay', SwitchType::class, [
                        'label' => $this->trans('Display', 'Modules.Scalexpertplugin.Admin'),
                        'choices' => [
                            $this->trans('Disabled', 'Modules.Scalexpertplugin.Admin') => false,
                            $this->trans('Enabled', 'Modules.Scalexpertplugin.Admin') => true,
                        ],
                        'default_empty_data' => false,
                    ])
                    ->add($solution['solutionCode'] . DesignCustomizeFormDataConfiguration::ID_DELIMITER . 'productPosition', ChoiceType::class, [
                        'label' => $this->trans('Hook', 'Modules.Scalexpertplugin.Admin'),
                        'choices' => [
                            $this->trans('Under the add to cart bloc', 'Modules.Scalexpertplugin.Admin') => 'displayProductAdditionalInfo',
                        ],
                    ])
                    ->add($solution['solutionCode'] . DesignCustomizeFormDataConfiguration::ID_DELIMITER . 'productTitle', TextType::class, [
                        'label' => $this->trans('Title', 'Modules.Scalexpertplugin.Admin'),
                        'required' => false,
                    ]);

                if (!empty($solution['type']) && $solution['type'] == 'insurance') {
                    $builder->add($solution['solutionCode'] . DesignCustomizeFormDataConfiguration::ID_DELIMITER . 'productSubtitle', TextType::class, [
                        'label' => $this->trans('Sub-title', 'Modules.Scalexpertplugin.Admin'),
                        'required' => false,
                    ]);
                }

                $builder
                    ->add($solution['solutionCode'] . DesignCustomizeFormDataConfiguration::ID_DELIMITER . 'productDisplayLogo', SwitchType::class, [
                        'label' => $this->trans('Display logo', 'Modules.Scalexpertplugin.Admin'),
                        'choices' => [
                            $this->trans('Disabled', 'Modules.Scalexpertplugin.Admin') => false,
                            $this->trans('Enabled', 'Modules.Scalexpertplugin.Admin') => true,
                        ],
                        'default_empty_data' => false,
                    ])
                    ->add($solution['solutionCode'] . DesignCustomizeFormDataConfiguration::ID_DELIMITER . 'cartDisplay', SwitchType::class, [
                        'label' => $this->trans('Display', 'Modules.Scalexpertplugin.Admin'),
                        'choices' => [
                            $this->trans('Disabled', 'Modules.Scalexpertplugin.Admin') => false,
                            $this->trans('Enabled', 'Modules.Scalexpertplugin.Admin') => true,
                        ],
                        'default_empty_data' => false,
                    ])
                    ->add($solution['solutionCode'] . DesignCustomizeFormDataConfiguration::ID_DELIMITER . 'cartPosition', ChoiceType::class, [
                        'label' => $this->trans('Position', 'Modules.Scalexpertplugin.Admin'),
                        'choices' => [
                            $this->trans('displayShoppingCartFooter', 'Modules.Scalexpertplugin.Admin') => 'displayShoppingCartFooter',
                        ],
                    ])
                    ->add($solution['solutionCode'] . DesignCustomizeFormDataConfiguration::ID_DELIMITER . 'cartTitle', TextType::class, [
                        'label' => $this->trans('Title', 'Modules.Scalexpertplugin.Admin'),
                        'required' => false,
                    ])
                    ->add($solution['solutionCode'] . DesignCustomizeFormDataConfiguration::ID_DELIMITER . 'cartDisplayLogo', SwitchType::class, [
                        'label' => $this->trans('Display logo', 'Modules.Scalexpertplugin.Admin'),
                        'choices' => [
                            $this->trans('Disabled', 'Modules.Scalexpertplugin.Admin') => false,
                            $this->trans('Enabled', 'Modules.Scalexpertplugin.Admin') => true,
                        ],
                        'default_empty_data' => false,
                    ]);

                if (!empty($solution['type']) && $solution['type'] === 'financial') {
                    $builder
                        ->add($solution['solutionCode'] . DesignCustomizeFormDataConfiguration::ID_DELIMITER . 'paymentTitle', TextType::class, [
                            'label' => $this->trans('Title', 'Modules.Scalexpertplugin.Admin'),
                            'required' => false,
                        ])
                        ->add($solution['solutionCode'] . DesignCustomizeFormDataConfiguration::ID_DELIMITER . 'paymentDisplayLogo', SwitchType::class, [
                            'label' => $this->trans('Display logo', 'Modules.Scalexpertplugin.Admin'),
                            'choices' => [
                                $this->trans('Disabled', 'Modules.Scalexpertplugin.Admin') => false,
                                $this->trans('Enabled', 'Modules.Scalexpertplugin.Admin') => true,
                            ],
                            'default_empty_data' => false,
                        ]);
                }
            }
        }
    }
}
