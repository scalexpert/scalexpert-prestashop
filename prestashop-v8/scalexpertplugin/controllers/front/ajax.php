<?php
/**
 * Copyright © Scalexpert.
 * This file is part of Scalexpert plugin for PrestaShop. See COPYING.md for license details.
 *
 * @author    Scalexpert (https://scalexpert.societegenerale.com/)
 * @copyright Scalexpert
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

use ScalexpertPlugin\Form\Configuration\FinancingConfigurationFormDataConfiguration;
use ScalexpertPlugin\Form\Customize\DesignCustomizeFormDataConfiguration;

class ScalexpertpluginAjaxModuleFrontController extends ModuleFrontController
{
    public function displayAjaxGetFinancialInsertsOnProduct()
    {
        $productID = Tools::getValue('id_product', null);
        $financialInserts = [];

        if (!empty($productID)) {
            $availableSolutionsService = $this->get('scalexpert.service.available_solutions');
            $availableFinancialSolutions = $availableSolutionsService->getAvailableFinancialSolutions($productID);

            if (!empty($availableFinancialSolutions)) {
                $sortedSolutions = [];
                foreach ($availableFinancialSolutions as $availableFinancialSolution) {
                    $financialSolutionData = $availableFinancialSolution;

                    if (!empty($financialSolutionData['designConfiguration'])) {
                        if (isset($financialSolutionData['designConfiguration']['productDisplay']) && !$financialSolutionData['designConfiguration']['productDisplay']) {
                            continue;
                        }

                        if (isset($financialSolutionData['designConfiguration']['productDisplayLogo'])) {
                            $financialSolutionData['displayLogo'] = $financialSolutionData['designConfiguration']['productDisplayLogo'];
                        }

                        if (isset($financialSolutionData['designConfiguration']['productPosition'])) {
                            $financialSolutionData['position'] = $financialSolutionData['designConfiguration']['productPosition'];
                        }

                        if (isset($financialSolutionData['designConfiguration']['productTitle'])) {
                            $financialSolutionData['visualTitle'] = $financialSolutionData['designConfiguration']['productTitle'];
                        }

                        if (isset($financialSolutionData['designConfiguration']['position'])) {
                            $financialSolutionData['position'] = $financialSolutionData['designConfiguration']['position'];
                        }
                    }

                    $sortedSolutions[] = $financialSolutionData;
                }

                $this->module->sortSolutionsByPosition($sortedSolutions);

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
            }
        }

        $this->ajaxDie(json_encode(['financialInserts' => $financialInserts]));
    }

    public function displayAjaxGetInsuranceInserts()
    {
        $insuranceInserts = [];
        $productID = Tools::getValue('id_product', null);
        $productIDAttribute = Tools::getValue('id_product_attribute', null);

        $combinationGroupsData = Tools::getValue('combination_groups');
        if (!empty($combinationGroupsData)) {
            $productIDAttribute = (int) Product::getIdProductAttributeByIdAttributes(
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

        $availableSolutionsService = $this->get('scalexpert.service.available_solutions');
        $availableInsuranceSolutions = $availableSolutionsService->getAvailableInsuranceSolutions($productID, $productIDAttribute);

        if (!empty($availableInsuranceSolutions)) {
            foreach ($availableInsuranceSolutions as $availableInsuranceSolution) {
                $insuranceSolutionData = $availableInsuranceSolution;
                $insuranceSolutionData['id_product'] = $productID;
                $insuranceSolutionData['id_product_attribute'] = $productIDAttribute;

                if (!empty($insuranceSolutionData['designConfiguration'])) {
                    if (isset($insuranceSolutionData['designConfiguration'][$pageContext . '_display']) && !$insuranceSolutionData['designConfiguration'][$pageContext . '_display']) {
                        continue;
                    }

                    if (isset($insuranceSolutionData['designConfiguration'][$pageContext . '_display_logo'])) {
                        $insuranceSolutionData['displayLogo'] = $insuranceSolutionData['designConfiguration'][$pageContext . '_display_logo'];
                    }

                    if (isset($insuranceSolutionData['designConfiguration'][$pageContext . '_position'])) {
                        $insuranceSolutionData['position'] = $insuranceSolutionData['designConfiguration'][$pageContext . '_position'];
                    }

                    if (isset($insuranceSolutionData['designConfiguration'][$pageContext . '_title'])) {
                        $insuranceSolutionData['visualTitle'] = $insuranceSolutionData['designConfiguration'][$pageContext . '_title'];
                    }
                }

                $this->context->smarty->assign(['insuranceSolution' => $insuranceSolutionData]);

                $formattedInsert = $this->context->smarty->fetch(
                    'module:' . $this->module->name . '/views/templates/hook/insurance-insert.tpl'
                );

                if (empty($insuranceSolutionData[$pageContext . '_position'])) {
                    if ($pageContext === 'cart') {
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
        }

        $this->ajaxRender(json_encode(['insuranceInserts' => $insuranceInserts]));
    }
}
