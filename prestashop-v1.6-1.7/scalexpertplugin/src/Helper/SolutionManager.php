<?php

namespace ScalexpertPlugin\Helper;

class SolutionManager
{
    const TYPES = [
        0 => 'financials',
        1 => 'insurance',
    ];

    public static function getSolutionDisplayName($solutionCode, $module): string
    {
        $data = [
            'SCFRSP-3XTS' => $module->l('Paiement en 3X sans frais (SCFRSP-3XTS)'),
            'SCFRSP-3XPS' => $module->l('Paiement en 3X frais partagés (SCFRSP-3XPS)'),
            'SCFRSP-4XTS' => $module->l('Paiement en 4X sans frais (SCFRSP-4XTS)'),
            'SCFRSP-4XPS' => $module->l('Paiement en 4X frais partagés (SCFRSP-4XPS)'),
            'SCDELT-DXTS' => $module->l('Crédit long frais partagés (SCDELT-DXTS)'),
            'SCDELT-DXCO' => $module->l('Crédit long (SCDELT-DXCO)'),
            'SCFRLT-TXPS' => $module->l('Crédit long frais partagés (SCFRLT-TXPS)'),
            'SCFRLT-TXNO' => $module->l('Crédit long (SCFRLT-TXNO)'),
            'SCFRLT-TXTS' => $module->l('Crédit long sans frais (SCFRLT-TXTS)'),
            'CIFRWE-DXCO' => $module->l('Extention de garantie'),
        ];

        return $data[$solutionCode] ?? '';
    }

    public static function getSolutionFlag($solutionCode): string
    {
        $data = [
            'SCFRSP-3XTS' => 'FR',
            'SCFRSP-3XPS' => 'FR',
            'SCFRSP-4XTS' => 'FR',
            'SCFRSP-4XPS' => 'FR',
            'SCDELT-DXTS' => 'DE',
            'SCDELT-DXCO' => 'DE',
            'SCFRLT-TXPS' => 'FR',
            'SCFRLT-TXNO' => 'FR',
            'SCFRLT-TXTS' => 'FR',
            'CIFRWE-DXCO' => 'FR',
        ];

        return $data[$solutionCode] ?? '';
    }

    public static function getMissingSolution($existingSolution, $type): array
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
                ]
            ]
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
                foreach ($solutionGroup as $element) {
                    $missingSolution[] = $element;
                }
            }
        }

        return $missingSolution;
    }

    public static function getNewContractUrlByLang(string $offerCode, string $isoCode): string
    {
        $data = [
            self::TYPES[0] => [
                'en' => 'https://scalexpert.societegenerale.com/app/en/page/e-financing',
                'fr' => 'https://scalexpert.societegenerale.com/app/fr/page/e-financement',
            ],
            self::TYPES[1] => [
                'en' => 'https://scalexpert.societegenerale.com/app/en/page/warranty',
                'fr' => 'https://scalexpert.societegenerale.com/app/fr/page/garantie',
            ],
        ];

        return $data[strtolower($offerCode)][strtolower($isoCode)] ?? $data[self::TYPES[0]]['en'];
    }

    public static function getFinancialStateName($state, $module): string
    {
        switch ($state) {
            case 'ACCEPTED':
                $status = $module->l('Financing request accepted');
                break;
            case 'INITIALIZED':
                $status = $module->l('Financing request in progress');
                break;
            case 'REQUESTED':
                $status = $module->l('Financing request requested');
                break;
            case 'PRE_ACCEPTED':
                $status = $module->l('Financing request pre-accepted');
                break;
            case 'REJECTED':
                $status = $module->l('Financing request rejected');
                break;
            case 'CANCELLED':
                $status = $module->l('Financing request cancelled');
                break;
            case 'ABORTED':
                $status = $module->l('Financing request aborted');
                break;
            default:
                $status = $module->l('A technical error occurred during process, please retry.');
                break;
        }

        return $status;
    }

    public static function getFinancialStateLabel($state, $module, $withENStatus = false): string
    {
        switch ($state) {
            case 'INITIALIZED':
                $status = $module->l('Credit subscription is initialized') . ($withENStatus ? ' (INITIALIZED)' : '');
                break;
            case 'PRE_ACCEPTED':
                $status = $module->l('Credit subscription is completed, awaiting a final decision from the financial institution') . ($withENStatus ? ' (PRE_ACCEPTED)' : '');
                break;
            case 'ACCEPTED':
                $status = $module->l('Credit subscription is accepted') . ($withENStatus ? ' (ACCEPTED)' : '');
                break;
            case 'REJECTED':
                $status = $module->l('Credit subscription is refused') . ($withENStatus ? ' (REJECTED)' : '');
                break;
            case 'ABORTED':
                $status = $module->l('Credit subscription is aborted by the customer or due to technical issue') . ($withENStatus ? ' (ABORTED)' : '');
                break;
            case 'CANCELLED':
                $status = $module->l('Credit subscription is cancelled') . ($withENStatus ? ' (CANCELLED)' : '');
                break;
            default:
                $status = '';
                break;
        }

        return $status;
    }

    public static function getFinancialSubStateName($idOrderState, $module): string
    {
        switch ($idOrderState) {
            case 'SUBSCRIPTION IN PROGRESS':
                $status = $module->l('Souscription en cours par le client');
                break;
            case 'SUBSCRIPTION PRE-APPROVED BY PRODUCER - WAITING FOR CONTRACT SIGNATURE':
                $status = $module->l('En attente de signature client');
                break;
            case 'CONTRACT SIGNED BY CUSTOMER':
                $status = $module->l('Contrat signé par le client');
                break;
            case 'WAITING CUSTOMER FOR ADDITIONAL INFORMATION':
                $status = $module->l('Informations complémentaires demandées au client');
                break;
            case 'SUBSCRIPTION APPROVED BY PRODUCER':
                $status = $module->l('Souscription acceptée par l’organisme prêteur');
                break;
            case 'WAITING FOR THE DELIVERY CONFIRMATION':
                $status = $module->l('En attente de confirmation de livraison des produits par le marchand');
                break;
            case 'AWAITING PAYMENT TO MERCHANT':
                $status = $module->l('En attente du paiement de la commande par l’organisme prêteur');
                break;
            case 'PAYMENT HAS BEEN ISSUED TO MERCHANT':
                $status = $module->l('Paiement de la commande réalisé par l’organisme prêteur');
                break;
            case 'SUBSCRIPTION REJECTED BY PRODUCER':
                $status = $module->l('Refus de l’organisme prêteur');
                break;
            case 'ABORTED BY CUSTOMER':
                $status = $module->l('Abandon de la souscription par le client');
                break;
            case 'ABORTED DUE TO TECHNICAL ISSUE':
                $status = $module->l('Incident technique lors de la souscription');
                break;
            case 'CANCELED BY CUSTOMER':
                $status = $module->l('Annulation demandée par le client');
                break;
            default:
                $status = '';
                break;
        }

        return $status;
    }

    public static function getInsuranceStateName($idOrderState, $module): string
    {
        switch ($idOrderState) {
            case 'ACTIVATED':
                $status = $module->l('Insurance subscription activated');
                break;
            case 'INITIALIZED':
                $status = $module->l('Insurance subscription request in progress');
                break;
            case 'SUBSCRIBED':
                $status = $module->l('Insurance subscribed');
                break;
            case 'REJECTED':
                $status = $module->l('Insurance subscription rejected');
                break;
            case 'CANCELLED':
                $status = $module->l('Insurance subscription cancelled');
                break;
            case 'TERMINATED':
                $status = $module->l('Insurance subscription terminated');
                break;
            case 'ABORTED':
                $status = $module->l('Insurance subscription aborted');
                break;
            default:
                $status = $module->l('A technical error occurred during process, please retry.');
                break;
        }

        return $status;
    }

    public static function isFrenchLongFinancingSolution($solutionCode): bool
    {
        return in_array($solutionCode, [
            'SCFRLT-TXPS',
            'SCFRLT-TXNO',
            'SCFRLT-TXTS',
        ], true);
    }

    public static function isDeutschLongFinancingSolution($solutionCode): bool
    {
        return in_array($solutionCode, [
            'SCDELT-DXTS',
            'SCDELT-DXCO',
        ], true);
    }

    public static function isLongFinancingSolution(string $solutionCode): bool
    {
        return
            static::isFrenchLongFinancingSolution($solutionCode)
            || static::isDeutschLongFinancingSolution($solutionCode)
            ;
    }

    public static function hasFeesFinancingSolution(string $solutionCode): bool
    {
        return in_array($solutionCode, [
            'SCFRSP-3XPS',
            'SCFRSP-4XPS',
            'SCFRLT-TXPS',
        ], true);
    }

    public static function getOperators(
        $module,
        $isDeutsch = false
    ): array
    {
        return $isDeutsch ? [] : [
            'UPS' => $module->l('UPS'),
            'DHL' => $module->l('DHL'),
            'CHRONOPOST' => $module->l('CHRONOPOST'),
            'LA_POSTE' => $module->l('LA_POSTE'),
            'DPD' => $module->l('DPD'),
            'RELAIS_COLIS' => $module->l('RELAIS_COLIS'),
            'MONDIAL_RELAY' => $module->l('MONDIAL_RELAY'),
            'FEDEX' => $module->l('FEDEX'),
            'GLS' => $module->l('GLS'),
            'UNKNOWN' => $module->l('UNKNOWN'),
        ];
    }
}
