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

use PrestaShopBundle\Form\Admin\Type\TranslatorAwareType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;

class MappingConfigurationFormType extends TranslatorAwareType
{
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
                    'label' => $this->getLabelByStatus($financialState),
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

    private function getLabelByStatus($status)
    {
        switch ($status) {
            case 'INITIALIZED':
                $status = $this->trans(
                    'Credit subscription is initialized (INITIALIZED)',
                    'Modules.Scalexpertplugin.Admin'
                );
                break;
            case 'PRE_ACCEPTED':
                $status = $this->trans(
                    'Credit subscription is completed, awaiting a final decision from the financial institution (PRE_ACCEPTED)',
                    'Modules.Scalexpertplugin.Admin'
                );
                break;
            case 'ACCEPTED':
                $status = $this->trans(
                    'Credit subscription is accepted (ACCEPTED)',
                    'Modules.Scalexpertplugin.Admin'
                );
                break;
            case 'REJECTED':
                $status = $this->trans(
                    'Credit subscription is refused (REJECTED)',
                    'Modules.Scalexpertplugin.Admin'
                );
                break;
            case 'ABORTED':
                $status = $this->trans(
                    'Credit subscription is aborted by the customer or due to technical issue (ABORTED)',
                    'Modules.Scalexpertplugin.Admin'
                );
                break;
            case 'CANCELLED':
                $status = $this->trans(
                    'Credit subscription is cancelled (CANCELLED)',
                    'Modules.Scalexpertplugin.Admin'
                );
                break;
            default:
                $status = '';
                break;
        }

        return $status;
    }
}
