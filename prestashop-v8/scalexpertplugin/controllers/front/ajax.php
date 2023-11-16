<?php
/**
 * Copyright © Scalexpert.
 * This file is part of Scalexpert plugin for PrestaShop.
 *
 * @author    Société Générale
 * @copyright Scalexpert
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
                foreach ($availableFinancialSolutions as $availableFinancialSolution) {
                    $financialSolutionData = $availableFinancialSolution;

                    if (!empty($financialSolutionData['designConfiguration'])) {
                        if (isset($financialSolutionData['designConfiguration']['product_display']) && !$financialSolutionData['designConfiguration']['product_display']) {
                            continue;
                        }

                        if (isset($financialSolutionData['designConfiguration']['product_display_logo'])) {
                            $financialSolutionData['displayLogo'] = $financialSolutionData['designConfiguration']['product_display_logo'];
                        }

                        if (isset($financialSolutionData['designConfiguration']['product_position'])) {
                            $financialSolutionData['position'] = $financialSolutionData['designConfiguration']['product_position'];
                        }

                        if (isset($financialSolutionData['designConfiguration']['product_title'])) {
                            $financialSolutionData['visualTitle'] = $financialSolutionData['designConfiguration']['product_title'];
                        }
                    }

                    $this->context->smarty->assign(['financialSolution' => $financialSolutionData]);
                    $formattedInsert = $this->context->smarty->fetch(
                        'module:' . $this->module->name . '/views/templates/hook/financial-insert.tpl'
                    );

                    if (empty($financialSolutionData['product_position'])) {
                        $financialSolutionData['product_position'] = 'displayProductAdditionalInfo';
                    }

                    $financialInserts[] = [
                        'formattedInsert' => $formattedInsert,
                        'position' => $financialSolutionData['product_position'],
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

                    if (isset($financialSolutionData['designConfiguration'][$pageContext . '_position'])) {
                        $insuranceSolutionData['position'] = $financialSolutionData['designConfiguration'][$pageContext . '_position'];
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

        $this->ajaxDie(json_encode(['insuranceInserts' => $insuranceInserts]));
    }
}