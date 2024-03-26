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
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Translation\TranslatorInterface;

class RegroupPaymentsConfigurationFormType extends TranslatorAwareType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(RegroupPaymentsConfigurationFormDataConfiguration::CONFIGURATION_REGROUP_PAYMENTS, SwitchType::class, [
            'label' => $this->trans('Regroup all payment options', 'Modules.Scalexpertplugin.Admin'),
            'choices' => [
                $this->trans('Disabled', 'Modules.Scalexpertplugin.Admin') => false,
                $this->trans('Enabled', 'Modules.Scalexpertplugin.Admin') => true,
            ],
            'default_empty_data' => false,
        ]);
    }
}
