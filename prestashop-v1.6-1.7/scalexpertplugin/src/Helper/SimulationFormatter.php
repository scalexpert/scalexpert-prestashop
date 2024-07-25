<?php
/**
 * Copyright © Scalexpert.
 * This file is part of Scalexpert plugin for PrestaShop. See COPYING.md for license details.
 *
 * @author    Scalexpert (https://scalexpert.societegenerale.com/)
 * @copyright Scalexpert
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */


namespace ScalexpertPlugin\Helper;

use ScalexpertPlugin\Service\SolutionSorter;

class SimulationFormatter
{
    public static function normalizeSimulations(
        $simulateResponse,
        $designSolution = [],
        $groupSolutions = false,
        $sortBySolutionCode = false
    ): array
    {
        $solutionSimulations = $simulateResponse['solutionSimulations'];
        if (empty($solutionSimulations)) {
            return [];
        }

        foreach ($solutionSimulations as $k => $solutionSimulation) {
            $solutionCode = $solutionSimulation['solutionCode'];

            if (!empty($designSolution)) {
                $solutionSimulations[$k]['designConfiguration'] = $designSolution[$solutionCode];
            }

            // Add context data
            $solutionSimulations[$k]['isLongFinancingSolution'] = SolutionManager::isLongFinancingSolution($solutionCode);
            $solutionSimulations[$k]['hasFeesSolution'] = SolutionManager::hasFeesFinancingSolution($solutionCode);
            $solutionSimulations[$k]['hasFeesOnFirstInstallment'] =
                $solutionSimulations[$k]['hasFeesSolution']
                && 0 < $solutionSimulations[$k]['simulations'][0]['feesAmount']
            ;

            foreach ($solutionSimulation['simulations'] as $j => $simulation) {
                // Refactor percentage data
                $solutionSimulations[$k]['simulations'][$j]['effectiveAnnualPercentageRateFormatted'] = $simulation['effectiveAnnualPercentageRate'] . '%';
                $solutionSimulations[$k]['simulations'][$j]['nominalPercentageRateFormatted'] = $simulation['nominalPercentageRate'] . '%';

                // Refactor price data
                $solutionSimulations[$k]['simulations'][$j]['totalCostFormatted'] = \Tools::displayPrice(
                    $simulation['totalCost']
                );
                $solutionSimulations[$k]['simulations'][$j]['dueTotalAmountFormatted'] = \Tools::displayPrice(
                    $simulation['dueTotalAmount']
                );
                $solutionSimulations[$k]['simulations'][$j]['feesAmountFormatted'] = \Tools::displayPrice(
                    $simulation['feesAmount']
                );

                foreach ($simulation['installments'] as $i => $installment) {
                    // Refactor price data
                    $solutionSimulations[$k]['simulations'][$j]['installments'][$i]['amountFormatted'] = \Tools::displayPrice(
                        $installment['amount']
                    );
                }
            }
        }

        $simulationsFullData = [];
        if ($groupSolutions) {
            // Group financing solutions by having fees or not
            foreach ($solutionSimulations as $solutionSimulation) {
                foreach ($solutionSimulation['simulations'] as $simulation) {
                    $simulation['designConfiguration'] = $solutionSimulation['designConfiguration'];
                    $simulation['isLongFinancingSolution'] = $solutionSimulation['isLongFinancingSolution'];
                    $simulation['hasFeesOnFirstInstallment'] =
                        $solutionSimulation['hasFeesSolution']
                        && 0 < $simulation['feesAmount']
                    ;

                    if ($sortBySolutionCode) {
                        $simulationsFullData[$solutionSimulation['solutionCode']][] = $simulation;
                    } else {
                        $simulationsFullData['all'][] = $simulation;
                    }
                }
            }

            // Sort by duration
            if ($sortBySolutionCode) {
                foreach ($simulationsFullData as $simulationsFullDatum) {
                    SolutionSorter::sortSolutionsByDuration($simulationsFullDatum);
                }
            } else {
                SolutionSorter::sortSolutionsByDuration($simulationsFullData['all']);
            }
        } else {
            foreach ($solutionSimulations as $solutionSimulation) {
                $solutionCode = $solutionSimulation['solutionCode'];
                $simulationsFullData[$solutionCode] = reset($solutionSimulation['simulations']);
                $simulationsFullData[$solutionCode]['designConfiguration'] = $designSolution[$solutionCode];
                $simulationsFullData[$solutionCode]['isLongFinancingSolution'] = $solutionSimulation['isLongFinancingSolution'];
                $simulationsFullData[$solutionCode]['hasFeesOnFirstInstallment'] = $solutionSimulation['hasFeesOnFirstInstallment'];
            }
        }

        return $simulationsFullData;
    }
}
