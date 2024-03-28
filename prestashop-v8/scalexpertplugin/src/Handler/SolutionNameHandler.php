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
            'SCFRSP-3XTS' => 'Paiement en 3X (option sans frais)',
            'SCFRSP-3XPS' => 'Paiement en 3X (option avec frais)',
            'SCFRSP-4XTS' => 'Paiement en 4X (option sans frais)',
            'SCFRSP-4XPS' => 'Paiement en 4X (option avec frais)',
            'SCDELT-DXTS' => 'Crédit long (sans frais)',
            'SCDELT-DXCO' => 'Crédit long (avec commission)',
            'SCFRLT-TXPS' => 'Crédit long (sans frais)',
            'SCFRLT-TXNO' => 'Crédit long (avec commission)',

            'CIFRWE-DXCO' => 'Extention de garantie',
        ];

        if (isset($solutions[$solutionCode])) {
            return $solutions[$solutionCode];
        }

        return null;
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
            'SCFRLT-TXNO' => 'FR',
            'CIFRWE-DXCO' => 'FR',
        ];

        if (isset($data[$solutionCode])) {
            return $data[$solutionCode];
        }

        return null;
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
                $missingSolution = array_merge($missingSolution, $solutionGroup);
            }
        }

        return array_unique($missingSolution);
    }
}
