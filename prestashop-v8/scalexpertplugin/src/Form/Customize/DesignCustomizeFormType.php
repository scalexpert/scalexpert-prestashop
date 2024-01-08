<?php

declare(strict_types=1);

namespace ScalexpertPlugin\Form\Customize;

use PrestaShopBundle\Form\Admin\Type\CategoryChoiceTreeType;
use PrestaShopBundle\Form\Admin\Type\SwitchType;
use PrestaShopBundle\Form\Admin\Type\TranslatorAwareType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Translation\TranslatorInterface;

class DesignCustomizeFormType extends TranslatorAwareType
{
    private $apiClient;

    public function __construct(TranslatorInterface $translator, array $locales, $apiClient)
    {
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
                    ->add($solution['solutionCode'] . ':excluded_categories', CategoryChoiceTreeType::class, [
                            'label' => $this->trans('Excluded categories', 'Modules.Scalexpertplugin.Admin'),
                            'multiple' => true,
                        ]
                    )
                    ->add($solution['solutionCode'] . ':product_display', SwitchType::class, [
                        'label' => $this->trans('Display', 'Modules.Scalexpertplugin.Admin'),
                        'choices' => [
                            $this->trans('Disabled', 'Modules.Scalexpertplugin.Admin') => false,
                            $this->trans('Enabled', 'Modules.Scalexpertplugin.Admin') => true,
                        ],
                        'default_empty_data' => false,
                    ])
                    ->add($solution['solutionCode'] . ':product_position', ChoiceType::class, [
                        'label' => $this->trans('Position', 'Modules.Scalexpertplugin.Admin'),
                        'choices' => [
                            $this->trans('Under the add to cart bloc', 'Modules.Scalexpertplugin.Admin') => 'displayProductAdditionalInfo',
                        ],
                    ])
                    ->add($solution['solutionCode'] . ':product_title', TextType::class, [
                        'label' => $this->trans('Title', 'Modules.Scalexpertplugin.Admin'),
                        'required' => false,
                    ]);

                    if (!empty($solution['type']) && $solution['type'] == 'insurance') {
                        $builder->add($solution['solutionCode'] . ':product_subtitle', TextType::class, [
                            'label' => $this->trans('Sub-title', 'Modules.Scalexpertplugin.Admin'),
                            'required' => false,
                        ]);
                    }

                    $builder->add($solution['solutionCode'] . ':product_display_logo', SwitchType::class, [
                        'label' => $this->trans('Display logo', 'Modules.Scalexpertplugin.Admin'),
                        'choices' => [
                            $this->trans('Disabled', 'Modules.Scalexpertplugin.Admin') => false,
                            $this->trans('Enabled', 'Modules.Scalexpertplugin.Admin') => true,
                        ],
                        'default_empty_data' => false,
                    ]);

                    if (!empty($solution['type']) && $solution['type'] === 'insurance') {
                        $builder
                            ->add($solution['solutionCode'] . ':cart_display', SwitchType::class, [
                                'label' => $this->trans('Display', 'Modules.Scalexpertplugin.Admin'),
                                'choices' => [
                                    $this->trans('Disabled', 'Modules.Scalexpertplugin.Admin') => false,
                                    $this->trans('Enabled', 'Modules.Scalexpertplugin.Admin') => true,
                                ],
                                'default_empty_data' => false,
                            ])
                            ->add($solution['solutionCode'] . ':cart_position', ChoiceType::class, [
                                'label' => $this->trans('Position', 'Modules.Scalexpertplugin.Admin'),
                                'choices' => [
                                    $this->trans('displayShoppingCartFooter', 'Modules.Scalexpertplugin.Admin') => 'displayShoppingCartFooter',
                                ],
                            ])
                            ->add($solution['solutionCode'] . ':cart_title', TextType::class, [
                                'label' => $this->trans('Title', 'Modules.Scalexpertplugin.Admin'),
                                'required' => false,
                            ])
                            ->add($solution['solutionCode'] . ':cart_display_logo', SwitchType::class, [
                                'label' => $this->trans('Display logo', 'Modules.Scalexpertplugin.Admin'),
                                'choices' => [
                                    $this->trans('Disabled', 'Modules.Scalexpertplugin.Admin') => false,
                                    $this->trans('Enabled', 'Modules.Scalexpertplugin.Admin') => true,
                                ],
                                'default_empty_data' => false,
                            ]);
                    } else {
                        $builder
                            ->add($solution['solutionCode'] . ':payment_title', TextType::class, [
                                'label' => $this->trans('Title', 'Modules.Scalexpertplugin.Admin'),
                                'required' => false,
                            ])
                            ->add($solution['solutionCode'] . ':payment_display_logo', SwitchType::class, [
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
