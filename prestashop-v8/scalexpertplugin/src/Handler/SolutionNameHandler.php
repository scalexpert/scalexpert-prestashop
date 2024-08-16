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

class SolutionNameHandler
{
    public function getSolutionName($solutionCode): ?string
    {
        $solutions = [
            'SCFRSP-3XTS' => 'Paiement en 3X sans frais (SCFRSP-3XTS)',
            'SCFRSP-3XPS' => 'Paiement en 3X frais partagés (SCFRSP-3XPS)',
            'SCFRSP-4XTS' => 'Paiement en 4X sans frais (SCFRSP-4XTS)',
            'SCFRSP-4XPS' => 'Paiement en 4X frais partagés (SCFRSP-4XPS)',
            'SCDELT-DXTS' => 'Crédit long frais partagés (SCDELT-DXTS)',
            'SCDELT-DXCO' => 'Crédit long (SCDELT-DXCO)',
            'SCFRLT-TXPS' => 'Crédit long frais partagés (SCFRLT-TXPS)',
            'SCFRLT-TXTS' => 'Crédit long sans frais (SCFRLT-TXTS)',
            'SCFRLT-TXNO' => 'Crédit long (SCFRLT-TXNO)',
            'CIFRWE-DXCO' => 'Extension de garantie',
        ];

        return $solutions[$solutionCode] ?? null;
    }

    public function getSolutionFlag($solutionCode): ?string
    {
        $data = [
            'SCFRSP-3XTS' => 'FR',
            'SCFRSP-3XPS' => 'FR',
            'SCFRSP-4XTS' => 'FR',
            'SCFRSP-4XPS' => 'FR',
            'SCDELT-DXTS' => 'DE',
            'SCDELT-DXCO' => 'DE',
            'SCFRLT-TXPS' => 'FR',
            'SCFRLT-TXTS' => 'FR',
            'SCFRLT-TXNO' => 'FR',
            'CIFRWE-DXCO' => 'FR',
        ];

        return $data[$solutionCode] ?? null;
    }

    public function getMissingSolution($existingSolution, $type)
    {
        $solutionGrouped = [
            'financials' => [
                [
                    'SCFRSP-3XTS',
                    'SCFRSP-3XPS'
                ],
                [
                    'SCFRSP-4XTS',
                    'SCFRSP-4XPS'
                ],
                [
                    'SCDELT-DXTS',
                ],
                [
                    'SCDELT-DXCO',
                ],
                [
                    'SCFRLT-TXPS',
                    'SCFRLT-TXTS',
                ],
                [
                    'SCFRLT-TXNO',
                ],
            ],
            'insurance' => [
                [
                    'CIFRWE-DXCO',
                ],
            ],
        ];

        if (!isset($solutionGrouped[$type])) {
            return [];
        }

        $missingSolution = [];
        foreach ($solutionGrouped[$type] as $solutionGroup) {
            $found = false;

            foreach ($solutionGroup as $solutionCode) {
                if (in_array($solutionCode, $existingSolution)) {
                    $found = true;
                }
            }

            if (!$found) {
                $missingSolution[] = $solutionGroup;
            }
        }

        return array_unique(array_merge([], ...$missingSolution));
    }

    public function getFinancialStateName(
        $financingState,
        $translator,
        bool $backOfficeDisplay = false,
        bool $withENStatus = false
    )
    {
        $matchingFOStates = [
            'ACCEPTED' =>
                $translator->trans('Financing request accepted', [], 'Modules.Scalexpertplugin.Shop'),
            'INITIALIZED' =>
                $translator->trans('Financing request in progress', [], 'Modules.Scalexpertplugin.Shop'),
            'REQUESTED' =>
                $translator->trans('Financing request requested', [], 'Modules.Scalexpertplugin.Shop'),
            'PRE_ACCEPTED' =>
                $translator->trans('Financing request pre-accepted', [], 'Modules.Scalexpertplugin.Shop'),
            'REJECTED' =>
                $translator->trans('Financing request rejected', [], 'Modules.Scalexpertplugin.Shop'),
            'CANCELLED' =>
                $translator->trans('Financing request cancelled', [], 'Modules.Scalexpertplugin.Shop'),
            'ABORTED' =>
                $translator->trans('Financing request aborted', [], 'Modules.Scalexpertplugin.Shop')
        ];

        $matchingBOStates = [
            'INITIALIZED' =>
                $translator->trans('Credit subscription is initialized', [], 'Modules.Scalexpertplugin.Shop'),
            'PRE_ACCEPTED' =>
                $translator->trans('Credit subscription is completed, awaiting a final decision from the financial institution', [], 'Modules.Scalexpertplugin.Shop'),
            'ACCEPTED' =>
                $translator->trans('Credit subscription is accepted', [], 'Modules.Scalexpertplugin.Shop'),
            'REJECTED' =>
                $translator->trans('Credit subscription is refused', [], 'Modules.Scalexpertplugin.Shop'),
            'ABORTED' =>
                $translator->trans('Credit subscription is aborted by the customer or due to technical issue', [], 'Modules.Scalexpertplugin.Shop'),
            'CANCELLED' =>
                $translator->trans('Credit subscription is cancelled', [], 'Modules.Scalexpertplugin.Shop'),
        ];

        if ($backOfficeDisplay) {
            $suffix = $withENStatus ? sprintf(' (%s)', $financingState) : '';
            return $matchingBOStates[$financingState] ? $matchingBOStates[$financingState] . $suffix : '';
        }

        return $matchingFOStates[$financingState] ?? $translator->trans('A technical error occurred during process, please retry.', [], 'Modules.Scalexpertplugin.Shop');
    }

    public function getFinancialSubStateName($financingSubState, $translator)
    {
        $matchingStates = [
            'SUBSCRIPTION IN PROGRESS' => $translator->trans('Souscription en cours par le client', [], 'Modules.Scalexpertplugin.Shop'),
            'SUBSCRIPTION PRE-APPROVED BY PRODUCER - WAITING FOR CONTRACT SIGNATURE' => $translator->trans('En attente de signature client ', [], 'Modules.Scalexpertplugin.Shop'),
            'CONTRACT SIGNED BY CUSTOMER' => $translator->trans('Contrat signé par le client', [], 'Modules.Scalexpertplugin.Shop'),
            'WAITING CUSTOMER FOR ADDITIONAL INFORMATION' => $translator->trans('Informations complémentaires demandées au client', [], 'Modules.Scalexpertplugin.Shop'),
            'SUBSCRIPTION APPROVED BY PRODUCER' => $translator->trans('Souscription acceptée par l’organisme prêteur', [], 'Modules.Scalexpertplugin.Shop'),
            'WAITING FOR THE DELIVERY CONFIRMATION' => $translator->trans('En attente de confirmation de livraison des produits par le marchand', [], 'Modules.Scalexpertplugin.Shop'),
            'AWAITING PAYMENT TO MERCHANT' => $translator->trans('En attente du paiement de la commande par l’organisme prêteur', [], 'Modules.Scalexpertplugin.Shop'),
            'PAYMENT HAS BEEN ISSUED TO MERCHANT' => $translator->trans('Paiement de la commande réalisée par l’organisme prêteur', [], 'Modules.Scalexpertplugin.Shop'),
            'SUBSCRIPTION REJECTED BY PRODUCER' => $translator->trans('Refus de l’organisme prêteur', [], 'Modules.Scalexpertplugin.Shop'),
            'ABORTED BY CUSTOMER' => $translator->trans('Abandon de la souscription par le client', [], 'Modules.Scalexpertplugin.Shop'),
            'ABORTED DUE TO TECHNICAL ISSUE' => $translator->trans('Incident technique lors de la souscription', [], 'Modules.Scalexpertplugin.Shop'),
            'CANCELED BY CUSTOMER' => $translator->trans('Annulation demandée par le client', [], 'Modules.Scalexpertplugin.Shop'),
        ];

        return $matchingStates[$financingSubState] ?? '';
    }
}
