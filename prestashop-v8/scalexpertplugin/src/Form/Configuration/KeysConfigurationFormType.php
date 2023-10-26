<?php
/**
 * Copyright © Scalexpert.
 * This file is part of Scalexpert plugin for PrestaShop.
 *
 * @author    Société Générale
 * @copyright Scalexpert
 */

declare(strict_types=1);

namespace ScalexpertPlugin\Form\Configuration;

use Context;
use PrestaShopBundle\Form\Admin\Type\TranslatorAwareType;
use Symfony\Component\Form\Extension\Core\Type\ButtonType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class KeysConfigurationFormType extends TranslatorAwareType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $link = Context::getContext()->link;
        $checkKeysAjaxUrl = $link->getAdminLink(
            'AdminScalexpertPluginKeysTab',
            true,
            [
                'route' => 'scalexpert_controller_tabs_admin_keys_check'
            ]
        );

        $builder
            ->add('scalexpert_keys_type', ChoiceType::class, [
                'label' => $this->trans('Choose environment', 'Modules.Scalexpertplugin.Admin'),
                'choices' => [
                    $this->trans('Test', 'Modules.Scalexpertplugin.Admin') => 'test',
                    $this->trans('Production', 'Modules.Scalexpertplugin.Admin') => 'production',
                ],
            ])
            ->add('scalexpert_keys_id_test', TextType::class, [
                'label' => $this->trans('ID for test API', 'Modules.Scalexpertplugin.Admin'),
                'required' => false,
            ])
            ->add('scalexpert_keys_secret_test', TextType::class, [
                'label' => $this->trans('Secret for test API', 'Modules.Scalexpertplugin.Admin'),
                'required' => false,
                'attr' => [
                    'data-password' => 'yes',
                ],
            ])
            ->add('scalexpert_keys_id_prod', TextType::class, [
                'label' => $this->trans('ID for production API', 'Modules.Scalexpertplugin.Admin'),
                'required' => false,
            ])
            ->add('scalexpert_keys_secret_prod', TextType::class, [
                'label' => $this->trans('Secret for production API', 'Modules.Scalexpertplugin.Admin'),
                'required' => false,
                'attr' => [
                    'data-password' => 'yes',
                ],
            ])
            ->add('scalexpert_keys_check', ButtonType::class, [
                'label' => $this->trans('Check API credentials', 'Modules.Scalexpertplugin.Admin'),
                'attr' => [
                    'data-url' => $checkKeysAjaxUrl,
                ],
            ]);

    }
}
