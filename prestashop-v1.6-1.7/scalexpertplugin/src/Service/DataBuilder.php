<?php
/**
 * Copyright © Scalexpert.
 * This file is part of Scalexpert plugin for PrestaShop. See COPYING.md for license details.
 *
 * @author    Scalexpert (https://scalexpert.societegenerale.com/)
 * @copyright Scalexpert
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

namespace ScalexpertPlugin\Service;

class DataBuilder
{
    public static function buildDesignData(
        $eligibleSolutions,
        $customizeProduct,
        $isPayment = false,
        $isCart = false
    ): array
    {
        $solutionCodes = [];
        $designSolutions = [];

        foreach ($eligibleSolutions as $eligibleSolution) {
            $solutionCode = $eligibleSolution['solutionCode'];

            if (isset($customizeProduct[$solutionCode])) {
                // Built available solution codes array
                $solutionCodes[] = $solutionCode;

                // Prepare design config variables for front
                $designSolutions[$solutionCode] = $eligibleSolution['communicationKit'];
                $designSolutions[$solutionCode]['position'] = $customizeProduct[$solutionCode]['position'];
                if ($isPayment) {
                    $designSolutions[$solutionCode]['displayLogo'] = $customizeProduct[$solutionCode]['logo_payment'];
                    if (!empty($customizeProduct[$solutionCode]['title_payment'])) {
                        $designSolutions[$solutionCode]['visualTitle'] = $customizeProduct[$solutionCode]['title_payment'];
                    }
                } elseif ($isCart) {
                    $designSolutions[$solutionCode]['displayLogo'] = $customizeProduct[$solutionCode]['logo_cart'];
                    if (!empty($customizeProduct[$solutionCode]['title_cart'])) {
                        $designSolutions[$solutionCode]['visualTitle'] = $customizeProduct[$solutionCode]['title_cart'];
                    }
                } else {
                    $designSolutions[$solutionCode]['displayLogo'] = $customizeProduct[$solutionCode]['logo'];
                    if (!empty($customizeProduct[$solutionCode]['title'])) {
                        $designSolutions[$solutionCode]['visualTitle'] = $customizeProduct[$solutionCode]['title'];
                    }
                }

                $designSolutions[$solutionCode]['custom'] = $customizeProduct[$solutionCode];
            }
        }

        return [
            'solutionCodes' => $solutionCodes,
            'designSolutions' => $designSolutions
        ];
    }
}
