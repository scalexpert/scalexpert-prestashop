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

namespace ScalexpertPlugin\Handler;

class InsuranceNameHandler
{
    public function getInsuranceStateName($insuranceState, $translator)
    {
        $matchingStates = [
            'INITIALIZED' =>
                $translator->trans('Insurance subscription request in progress', [], 'Modules.Scalexpertplugin.Shop'),
            'ACTIVATED' =>
                $translator->trans('Insurance subscription activated', [], 'Modules.Scalexpertplugin.Shop'),
            'SUBSCRIBED' =>
                $translator->trans('Insurance subscribed', [], 'Modules.Scalexpertplugin.Shop'),
            'REJECTED' =>
                $translator->trans('Insurance subscription rejected', [], 'Modules.Scalexpertplugin.Shop'),
            'CANCELLED' =>
                $translator->trans('Insurance subscription cancelled', [], 'Modules.Scalexpertplugin.Shop'),
            'TERMINATED' =>
                $translator->trans('Insurance subscription terminated', [], 'Modules.Scalexpertplugin.Shop'),
            'ABORTED' =>
                $translator->trans('Insurance subscription aborted', [], 'Modules.Scalexpertplugin.Shop'),
        ];

        return $matchingStates[$insuranceState] ?? $translator->trans('A technical error occurred during process, please retry.', [], 'Modules.Scalexpertplugin.Shop');
    }
}
