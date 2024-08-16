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
