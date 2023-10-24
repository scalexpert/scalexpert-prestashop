<?php
/**
 * Copyright © Scalexpert.
 * This file is part of Scalexpert plugin for PrestaShop.
 *
 * @author    Société Générale
 * @copyright Scalexpert
 */

use DATASOLUTION\Module\Scalexpert\Api\Financing;
use DATASOLUTION\Module\Scalexpert\Api\Insurance;
use DATASOLUTION\Module\Scalexpert\Helper\FinancingEligibility;
use DATASOLUTION\Module\Scalexpert\Helper\InsuranceEligibility;

class ScalexpertPluginDisplayModuleFrontController extends ModuleFrontController
{
    public function displayAjaxProduct()
    {
        $type = \Tools::getValue('type');
        if (empty($type)) {
            return;
        }

        switch ($type) {
            case 'financial':
                $content = $this->_getFinancialsForProduct();
                break;
            case 'insurance':
                $content = $this->_getInsuranceForProduct();
                break;
        }

        $this->ajaxDie(\Tools::jsonEncode(array('hasError' => false, 'content' => $content)));
    }

    public function displayAjaxCart()
    {

        $content = $this->_getInsuranceForCart();

        $this->ajaxDie(\Tools::jsonEncode(array('hasError' => false, 'content' => $content)));
    }

    private function _getFinancialsForProduct()
    {
        $idProduct = (int)Tools::getValue('idProduct');
        $oProduct = new Product($idProduct);
        if (!Validate::isLoadedObject($oProduct)) {
            return;
        }

        $customizeProduct = FinancingEligibility::getEligibleSolutionByProduct($oProduct);
        if (empty($customizeProduct)) {
            return;
        }

        $content = '';
        $eligibleSolutions = Financing::getEligibleSolutionsForFront(
            floor($oProduct->getPrice()),
            strtoupper($this->context->language->iso_code)
        );

        foreach ($eligibleSolutions as $eligibleSolution) {

            if (isset($customizeProduct[$eligibleSolution['solutionCode']])) {
                $this->context->smarty->assign(
                    $this->module->name . '_productButtons',
                    [
                        'nameModule' => $this->module->name,
                        'idModal' => md5($eligibleSolution['solutionCode'].'_'.$this->module->name . 'hookDisplayProductButtons')
                    ]
                );
                $communicationKit = $eligibleSolution['communicationKit'];
                if (!empty($customizeProduct[$eligibleSolution['solutionCode']]['title'])) {
                    $communicationKit['visualTitle'] = $customizeProduct[$eligibleSolution['solutionCode']]['title'];
                }
                $communicationKit['displayLogo'] = $customizeProduct[$eligibleSolution['solutionCode']]['logo'];
                $this->context->smarty->assign(['availableSolution' => $communicationKit]);

                if (version_compare(_PS_VERSION_, '1.7', '>=')) {
                    $content .= $this->context->smarty->fetch(_PS_MODULE_DIR_ . 'scalexpertplugin/views/templates/hook/ps17/productFinancialContent.tpl');
                } else {
                    $content .= $this->context->smarty->fetch(_PS_MODULE_DIR_ . 'scalexpertplugin/views/templates/hook/ps16/productFinancialContent.tpl');
                }
            }
        }

        return $content;
    }

    private function _getInsuranceForProduct()
    {
        $idProduct = (int)Tools::getValue('idProduct');
        $oProduct = new Product($idProduct);
        if (!Validate::isLoadedObject($oProduct)) {
            return;
        }
        $idProductAttribute = (int)Tools::getValue('idProductAttribute');

        $customizeProduct = InsuranceEligibility::getEligibleSolutionByProduct($oProduct);
        if (empty($customizeProduct)) {
            return;
        }

        $content = '';
        $eligibleSolutions = Insurance::getEligibleSolutionsFromBuyerBillingCountry(
            strtoupper($this->context->language->iso_code)
        );

        foreach ($eligibleSolutions as $eligibleSolution) {

            if (isset($customizeProduct[$eligibleSolution['solutionCode']])) {
                $this->context->smarty->assign(
                    $this->module->name . '_productButtons',
                    [
                        'nameModule' => $this->module->name,
                        'idModal' => md5($eligibleSolution['solutionCode'].'_'.$this->module->name . 'hookDisplayProductButtons')
                    ]
                );
                $communicationKit = $eligibleSolution['communicationKit'];
                if (!empty($customizeProduct[$eligibleSolution['solutionCode']]['title'])) {
                    $communicationKit['visualTitle'] = $customizeProduct[$eligibleSolution['solutionCode']]['title'];
                }
                $communicationKit['displayLogo'] = $customizeProduct[$eligibleSolution['solutionCode']]['logo'];


                $itemData = Insurance::createItem($eligibleSolution['solutionCode'], $idProduct);
                if (!$itemData) {
                    continue;
                }

                $insurancesData = Insurance::searchInsurances(
                    $eligibleSolution['solutionCode'],
                    $itemData['id'],
                    $oProduct->getPrice(true, $idProductAttribute, 2)
                );

                if(!empty($communicationKit)) {
                    $insurancesData = array_merge($communicationKit, $insurancesData);
                }

                $this->context->smarty->assign([
                    'insurancesData' => $insurancesData,
                ]);

                if (version_compare(_PS_VERSION_, '1.7', '>=')) {
                    $content .= $this->context->smarty->fetch(_PS_MODULE_DIR_ . 'scalexpertplugin/views/templates/hook/ps17/productInsuranceContent.tpl');
                } else {
                    $content .= $this->context->smarty->fetch(_PS_MODULE_DIR_ . 'scalexpertplugin/views/templates/hook/ps16/productInsuranceContent.tpl');
                }
            }
        }

        return $content;
    }

    private function _getInsuranceForCart()
    {
        $oCart = Context::getContext()->cart;
        if (!Validate::isLoadedObject($oCart)) {
            return;
        }

        $products = $oCart->getProducts();
        if (!$products) {
            return;
        }

        $buyerCountry = strtoupper($this->context->language->iso_code);
        if ($oCart->id_address_invoice) {
            $oAddress = new Address($oCart->id_address_invoice);
            if (Validate::isLoadedObject($oAddress)) {
                $oCountry = new Country($oAddress->id_country);
                if (Validate::isLoadedObject($oCountry)) {
                    $buyerCountry = strtoupper($oCountry->iso_code);
                }
            }
        }

        //todo : ignore insurance product already in cart.

        $content = '';
        foreach ($products as $product) {
            $content .= $this->_getInsuranceForProductInCart($buyerCountry, $product['id_product'], $product['id_product_attribute']);
        }

        return $content;
    }

    private function _getInsuranceForProductInCart($buyerCountry, $idProduct, $idProductAttribute = 0)
    {
        $oProduct = new Product($idProduct);
        if (!Validate::isLoadedObject($oProduct)) {
            return;
        }

        $customizeProduct = InsuranceEligibility::getEligibleSolutionByProduct($oProduct);
        if (empty($customizeProduct)) {
            return;
        }

        $content = '';
        $eligibleSolutions = Insurance::getEligibleSolutionsFromBuyerBillingCountry(
            $buyerCountry
        );

        foreach ($eligibleSolutions as $eligibleSolution) {

            if (isset($customizeProduct[$eligibleSolution['solutionCode']])) {
                $this->context->smarty->assign(
                    $this->module->name . '_productButtons',
                    [
                        'nameModule' => $this->module->name,
                        'idModal' => md5($eligibleSolution['solutionCode'].'_'.$this->module->name . 'hookDisplayProductButtons_'.$idProduct.'_'.$idProductAttribute)
                    ]
                );
                $communicationKit = $eligibleSolution['communicationKit'];
                if (!empty($customizeProduct[$eligibleSolution['solutionCode']]['title'])) {
                    $communicationKit['visualTitle'] = $customizeProduct[$eligibleSolution['solutionCode']]['title'];
                }
                $communicationKit['displayLogo'] = $customizeProduct[$eligibleSolution['solutionCode']]['logo'];
                $this->context->smarty->assign($communicationKit);

                $itemData = Insurance::createItem($eligibleSolution['solutionCode'], $idProduct);
                if (!$itemData) {
                    continue;
                }

                $insurancesData = Insurance::searchInsurances(
                    $eligibleSolution['solutionCode'],
                    $itemData['id'],
                    $oProduct->getPrice(true, $idProductAttribute, 2)
                );
                $this->context->smarty->assign([
                    'insurancesData' => $insurancesData,
                ]);

                if (version_compare(_PS_VERSION_, '1.7', '>=')) {
                    $content .= $this->context->smarty->fetch(_PS_MODULE_DIR_ . 'scalexpertplugin/views/templates/hook/ps17/productInsuranceContent.tpl');
                } else {
                    $content .= $this->context->smarty->fetch(_PS_MODULE_DIR_ . 'scalexpertplugin/views/templates/hook/ps16/productInsuranceContent.tpl');
                }
            }
        }

        return $content;
    }
}