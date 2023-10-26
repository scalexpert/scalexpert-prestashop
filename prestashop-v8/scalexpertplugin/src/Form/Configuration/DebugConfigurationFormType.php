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

use PrestaShopBundle\Form\Admin\Type\SwitchType;
use PrestaShopBundle\Form\Admin\Type\TranslatorAwareType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Translation\TranslatorInterface;

class DebugConfigurationFormType extends TranslatorAwareType
{
    public function __construct(TranslatorInterface $translator, array $locales)
    {
        parent::__construct($translator, $locales);
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(DebugConfigurationFormDataConfiguration::CONFIGURATION_DEBUG, SwitchType::class, [
            'label' => $this->trans('Debug mode', 'Modules.Scalexpertplugin.Admin'),
            'choices' => [
                $this->trans('Disabled', 'Modules.Scalexpertplugin.Admin') => false,
                $this->trans('Enabled', 'Modules.Scalexpertplugin.Admin') => true,
            ],
            'default_empty_data' => false,
            'help' => $this->trans('The log files are available in the folder', 'Modules.Scalexpertplugin.Admin').' : "/modules/scalexpertplugin/logs/"',
        ]);
    }
}
