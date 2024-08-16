<?php
/**
 * Copyright © Scalexpert.
 * This file is part of Scalexpert plugin for PrestaShop. See COPYING.md for license details.
 *
 * @author    Scalexpert (https://scalexpert.societegenerale.com/)
 * @copyright Scalexpert
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

use ScalexpertPlugin\Service\AvailableSolutionsService;
use ScalexpertPlugin\Service\SolutionSorterService;

class ScalexpertpluginAjaxModuleFrontController extends ModuleFrontController
{
    public function displayAjaxGetFinancingSimulationInsertOnProduct(): void
    {
        $productID = Tools::getValue('id_product', null);
        $productAttributeID = Tools::getValue('id_product_attribute', null);

        if (empty($productID)) {
            return;
        }

        /* @var AvailableSolutionsService $availableSolutionsService */
        /* @var SolutionSorterService $solutionSorterService */
        $availableSolutionsService = $this->get('scalexpert.service.available_solutions');
        $solutionSorterService = $this->get('scalexpert.service.solution_sorter');
        $buyerBillingCountry = $availableSolutionsService->getContextBuyerBillingCountry();

        if ('fr' === $buyerBillingCountry) {
            $simulationInsert = $this->getSimulationInsert(
                $availableSolutionsService,
                $solutionSorterService,
                $productID,
                $productAttributeID
            );
            $this->ajaxDie(json_encode(['simulationInsert' => $simulationInsert]));
        } else {
            $financialInserts = $this->getFinancialInserts(
                $availableSolutionsService,
                $solutionSorterService,
                $productID
            );
            $this->ajaxDie(json_encode(['financialInserts' => $financialInserts]));
        }
    }

    public function displayAjaxGetFinancialInsertsOnCart(): void
    {
        /* @var AvailableSolutionsService $availableSolutionsService */
        /* @var SolutionSorterService $solutionSorterService */
        $availableSolutionsService = $this->get('scalexpert.service.available_solutions');
        $solutionSorterService = $this->get('scalexpert.service.solution_sorter');
        $buyerBillingCountry = $availableSolutionsService->getContextBuyerBillingCountry();

        if ('fr' === $buyerBillingCountry) {
            $simulationInsert = $this->getSimulationInsert(
                $availableSolutionsService,
                $solutionSorterService
            );
            $this->ajaxDie(json_encode(['simulationInsert' => $simulationInsert]));
        }
    }

    public function displayAjaxGetInsuranceInserts(): void
    {
        $productID = Tools::getValue('id_product', null);
        $productIDAttribute = Tools::getValue('id_product_attribute', null);
        $combinationGroupsData = Tools::getValue('combination_groups');
        if (!empty($combinationGroupsData)) {
            $productIDAttribute = (int)Product::getIdProductAttributeByIdAttributes(
                $productID,
                $combinationGroupsData,
                true
            );
        }

        if (!empty($productID)) {
            $pageContext = 'product';
        } else {
            $pageContext = 'cart';
        }

        /* @var AvailableSolutionsService $availableSolutionsService */
        $availableSolutionsService = $this->get('scalexpert.service.available_solutions');
        $availableInsuranceSolutions = $availableSolutionsService->getAvailableInsuranceSolutions(
            $pageContext,
            $productID,
            $productIDAttribute
        );
        $insuranceInserts = $this->getInsuranceInserts(
            $availableInsuranceSolutions,
            $productID,
            $productIDAttribute,
            $pageContext
        );

        $this->ajaxRender(json_encode(['insuranceInserts' => $insuranceInserts]));
    }

    private function getSimulationInsert(
        AvailableSolutionsService $availableSolutionsService,
        SolutionSorterService     $solutionSorterService,
                                  $productID = null,
                                  $productAttributeID = null
    ): string
    {
        $availableSimulation = $availableSolutionsService->getSimulationForAvailableFinancialSolutions(
            $productID,
            $productAttributeID,
            true
        );
        if (
            empty($availableSimulation)
            || !isset($availableSimulation['solutionSimulations'])
        ) {
            return '';
        }

        // Group financing solutions by having fees or not
        $groupedSolutionSimulations = [];
        foreach ($availableSimulation['solutionSimulations'] as $solutionSimulation) {
            foreach ($solutionSimulation['simulations'] as $simulation) {
                $simulation['designConfiguration'] = $solutionSimulation['designConfiguration'];
                $simulation['isLongFinancingSolution'] = $solutionSimulation['isLongFinancingSolution'];
                $simulation['hasFeesOnFirstInstallment'] =
                    $solutionSimulation['hasFeesSolution']
                    && 0 < $simulation['feesAmount'];

                $groupedSolutionSimulations['all'][] = $simulation;
            }
        }

        // Sort by duration
        if (!empty($groupedSolutionSimulations)) {
            $solutionSorterService->sortSolutionsByDuration($groupedSolutionSimulations['all']);
        }

        $this->context->smarty->assign([
            'solutionSimulations' => $groupedSolutionSimulations,
            'financedAmount' => $availableSimulation['financedAmount'] ?? '',
            'financedAmountFormatted' => $availableSimulation['financedAmountFormatted'] ?? '',
        ]);

        return $this->context->smarty->fetch(
            'module:' . $this->module->name . '/views/templates/hook/simulation-insert.tpl'
        );
    }

    private function getFinancialInserts(
        AvailableSolutionsService $availableSolutionsService,
        SolutionSorterService     $solutionSorterService,
                                  $productID = null
    ): array
    {
        $availableFinancialSolutions = $availableSolutionsService->getAvailableFinancialSolutions($productID);
        if (empty($availableFinancialSolutions)) {
            return [];
        }

        $sortedSolutions = [];
        foreach ($availableFinancialSolutions as $availableFinancialSolution) {
            $financialSolutionData = $availableFinancialSolution;

            if (!empty($financialSolutionData['designConfiguration'])) {
                if (
                    isset($financialSolutionData['designConfiguration']['productDisplay'])
                    && !$financialSolutionData['designConfiguration']['productDisplay']
                ) {
                    continue;
                }

                $financialSolutionData = $this->mergeDesignConfigurationToFinancialSolutionData($financialSolutionData);
            }

            $sortedSolutions[] = $financialSolutionData;
        }
        $solutionSorterService->sortSolutionsByPosition($sortedSolutions);

        $financialInserts = [];
        foreach ($sortedSolutions as $sortedSolution) {
            $this->context->smarty->assign(['financialSolution' => $sortedSolution]);
            $formattedInsert = $this->context->smarty->fetch(
                'module:' . $this->module->name . '/views/templates/hook/financial-insert.tpl'
            );

            if (empty($sortedSolution['productPosition'])) {
                $sortedSolution['productPosition'] = 'displayProductAdditionalInfo';
            }

            $financialInserts[] = [
                'formattedInsert' => $formattedInsert,
                'hook' => $sortedSolution['productPosition'],
            ];
        }

        return $financialInserts;
    }

    private function getInsuranceInserts(
        array $availableInsuranceSolutions,
              $productID,
              $productIDAttribute,
              $pageContext
    ): array
    {
        if (empty($availableInsuranceSolutions)) {
            return [];
        }

        $insuranceInserts = [];
        foreach ($availableInsuranceSolutions as $availableInsuranceSolution) {
            $insuranceSolutionData = $availableInsuranceSolution;
            $insuranceSolutionData['id_product'] = $productID;
            $insuranceSolutionData['id_product_attribute'] = $productIDAttribute;

            if (!empty($insuranceSolutionData['designConfiguration'])) {
                if (
                    isset($insuranceSolutionData['designConfiguration'][$pageContext . '_display'])
                    && !$insuranceSolutionData['designConfiguration'][$pageContext . '_display']
                ) {
                    continue;
                }

                $insuranceSolutionData = $this->mergeDesignConfigurationToInsuranceSolutionData(
                    $insuranceSolutionData,
                    $pageContext
                );
            }

            $this->context->smarty->assign(['insuranceSolution' => $insuranceSolutionData]);

            $formattedInsert = $this->context->smarty->fetch(
                'module:' . $this->module->name . '/views/templates/hook/insurance-insert.tpl'
            );

            if (empty($insuranceSolutionData[$pageContext . '_position'])) {
                if ('cart' === $pageContext) {
                    $insuranceSolutionData[$pageContext . '_position'] = 'displayShoppingCartFooter';
                } else {
                    $insuranceSolutionData[$pageContext . '_position'] = 'displayProductAdditionalInfo';
                }
            }

            $insuranceInserts[] = [
                'formattedInsert' => $formattedInsert,
                'position' => $insuranceSolutionData[$pageContext . '_position'],
            ];
        }

        return $insuranceInserts;
    }

    private function mergeDesignConfigurationToFinancialSolutionData(array $solutionData): array
    {
        if (isset($solutionData['designConfiguration']['productDisplayLogo'])) {
            $solutionData['displayLogo'] = $solutionData['designConfiguration']['productDisplayLogo'];
        }

        if (isset($solutionData['designConfiguration']['productPosition'])) {
            $solutionData['position'] = $solutionData['designConfiguration']['productPosition'];
        }

        if (isset($solutionData['designConfiguration']['productTitle'])) {
            $solutionData['visualTitle'] = $solutionData['designConfiguration']['productTitle'];
        }

        if (isset($solutionData['designConfiguration']['position'])) {
            $solutionData['position'] = $solutionData['designConfiguration']['position'];
        }

        return $solutionData;
    }

    private function mergeDesignConfigurationToInsuranceSolutionData(
        array $solutionData,
        string $pageContext
    ): array
    {
        if (isset($solutionData['designConfiguration'][$pageContext . '_display_logo'])) {
            $solutionData['displayLogo'] = $solutionData['designConfiguration'][$pageContext . '_display_logo'];
        }

        if (isset($solutionData['designConfiguration'][$pageContext . '_position'])) {
            $solutionData['position'] = $solutionData['designConfiguration'][$pageContext . '_position'];
        }

        if (isset($solutionData['designConfiguration'][$pageContext . '_title'])) {
            $solutionData['visualTitle'] = $solutionData['designConfiguration'][$pageContext . '_title'];
        }

        return $solutionData;
    }
}
