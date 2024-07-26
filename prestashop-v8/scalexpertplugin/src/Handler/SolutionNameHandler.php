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

    public function getFinancialStateName($orderState, $translator)
    {
        switch ($orderState) {
            case 'ACCEPTED':
                $state = $translator->trans('Financing request accepted', [], 'Modules.Scalexpertplugin.Shop');
                break;
            case 'INITIALIZED':
                $state = $translator->trans('Financing request in progress', [], 'Modules.Scalexpertplugin.Shop');
                break;
            case 'REQUESTED':
                $state = $translator->trans('Financing request requested', [], 'Modules.Scalexpertplugin.Shop');
                break;
            case 'PRE_ACCEPTED':
                $state = $translator->trans('Financing request pre-accepted', [], 'Modules.Scalexpertplugin.Shop');
                break;
            case 'REJECTED':
                $state = $translator->trans('Financing request rejected', [], 'Modules.Scalexpertplugin.Shop');
                break;
            case 'CANCELLED':
                $state = $translator->trans('Financing request cancelled', [], 'Modules.Scalexpertplugin.Shop');
                break;
            case 'ABORTED':
                $state = $translator->trans('Financing request aborted', [], 'Modules.Scalexpertplugin.Shop');
                break;
            default:
                $state = $translator->trans('A technical error occurred during process, please retry.', [], 'Modules.Scalexpertplugin.Shop');
                break;
        }

        return $state;
    }

    public function getFinancialStateLabel($orderState, $translator, bool $withENStatus = false)
    {
        switch ($orderState) {
            case 'INITIALIZED':
                $state = $translator->trans(
                    'Credit subscription is initialized',
                    [],
                    'Modules.Scalexpertplugin.Shop'
                ) . ($withENStatus ? ' (INITIALIZED)' : '');
                break;
            case 'PRE_ACCEPTED':
                $state = $translator->trans(
                    'Credit subscription is completed, awaiting a final decision from the financial institution',
                    [],
                    'Modules.Scalexpertplugin.Shop'
                ) . ($withENStatus ? ' (PRE_ACCEPTED)' : '');
                break;
            case 'ACCEPTED':
                $state = $translator->trans(
                    'Credit subscription is accepted',
                    [],
                    'Modules.Scalexpertplugin.Shop'
                ) . ($withENStatus ? ' (ACCEPTED)' : '');
                break;
            case 'REJECTED':
                $state = $translator->trans(
                    'Credit subscription is refused',
                    [],
                    'Modules.Scalexpertplugin.Shop'
                ) . ($withENStatus ? ' (REJECTED)' : '');
                break;
            case 'ABORTED':
                $state = $translator->trans(
                    'Credit subscription is aborted by the customer or due to technical issue',
                    [],
                    'Modules.Scalexpertplugin.Shop'
                ) . ($withENStatus ? ' (ABORTED)' : '');
                break;
            case 'CANCELLED':
                $state = $translator->trans(
                    'Credit subscription is cancelled',
                    [],
                    'Modules.Scalexpertplugin.Shop'
                ) . ($withENStatus ? ' (CANCELLED)' : '');
                break;
            default:
                $state = '';
                break;
        }

        return $state;
    }

    public function getFinancialSubStateName($orderState, $translator)
    {
        switch ($orderState) {
            case 'SUBSCRIPTION IN PROGRESS':
                $state = $translator->trans('Souscription en cours par le client', [], 'Modules.Scalexpertplugin.Shop');
                break;
            case 'SUBSCRIPTION PRE-APPROVED BY PRODUCER - WAITING FOR CONTRACT SIGNATURE':
                $state = $translator->trans('En attente de signature client ', [], 'Modules.Scalexpertplugin.Shop');
                break;
            case 'CONTRACT SIGNED BY CUSTOMER':
                $state = $translator->trans('Contrat signé par le client', [], 'Modules.Scalexpertplugin.Shop');
                break;
            case 'WAITING CUSTOMER FOR ADDITIONAL INFORMATION':
                $state = $translator->trans('Informations complémentaires demandées au client', [], 'Modules.Scalexpertplugin.Shop');
                break;
            case 'SUBSCRIPTION APPROVED BY PRODUCER':
                $state = $translator->trans('Souscription acceptée par l’organisme prêteur', [], 'Modules.Scalexpertplugin.Shop');
                break;
            case 'WAITING FOR THE DELIVERY CONFIRMATION':
                $state = $translator->trans('En attente de confirmation de livraison des produits par le marchand', [], 'Modules.Scalexpertplugin.Shop');
                break;
            case 'AWAITING PAYMENT TO MERCHANT':
                $state = $translator->trans('En attente du paiement de la commande par l’organisme prêteur', [], 'Modules.Scalexpertplugin.Shop');
                break;
            case 'PAYMENT HAS BEEN ISSUED TO MERCHANT':
                $state = $translator->trans('Paiement de la commande réalisée par l’organisme prêteur', [], 'Modules.Scalexpertplugin.Shop');
                break;
            case 'SUBSCRIPTION REJECTED BY PRODUCER':
                $state = $translator->trans('Refus de l’organisme prêteur', [], 'Modules.Scalexpertplugin.Shop');
                break;
            case 'ABORTED BY CUSTOMER':
                $state = $translator->trans('Abandon de la souscription par le client', [], 'Modules.Scalexpertplugin.Shop');
                break;
            case 'ABORTED DUE TO TECHNICAL ISSUE':
                $state = $translator->trans('Incident technique lors de la souscription', [], 'Modules.Scalexpertplugin.Shop');
                break;
            case 'CANCELED BY CUSTOMER':
                $state = $translator->trans('Annulation demandée par le client', [], 'Modules.Scalexpertplugin.Shop');
                break;
            default:
                $state = '';
                break;
        }

        return $state;
    }

    public function getInsuranceStateName($idOrderState, $translator)
    {
        switch ($idOrderState) {
            case 'ACTIVATED':
                $state = $translator->trans('Insurance subscription activated', [], 'Modules.Scalexpertplugin.Shop');
                break;
            case 'INITIALIZED':
                $state = $translator->trans('Insurance subscription request in progress', [], 'Modules.Scalexpertplugin.Shop');
                break;
            case 'SUBSCRIBED':
                $state = $translator->trans('Insurance subscribed', [], 'Modules.Scalexpertplugin.Shop');
                break;
            case 'REJECTED':
                $state = $translator->trans('Insurance subscription rejected', [], 'Modules.Scalexpertplugin.Shop');
                break;
            case 'CANCELLED':
                $state = $translator->trans('Insurance subscription cancelled', [], 'Modules.Scalexpertplugin.Shop');
                break;
            case 'TERMINATED':
                $state = $translator->trans('Insurance subscription terminated', [], 'Modules.Scalexpertplugin.Shop');
                break;
            case 'ABORTED':
                $state = $translator->trans('Insurance subscription aborted', [], 'Modules.Scalexpertplugin.Shop');
                break;
            default:
                $state = $translator->trans('A technical error occurred during process, please retry.', [], 'Modules.Scalexpertplugin.Shop');
                break;
        }

        return $state;
    }
}
