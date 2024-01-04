<?php
/**
 * Copyright © Scalexpert.
 * This file is part of Scalexpert plugin for PrestaShop.
 *
 * @author    Société Générale
 * @copyright Scalexpert
 */

use ScalexpertPlugin\Api\Financing;
use ScalexpertPlugin\Api\Insurance;
use ScalexpertPlugin\Helper\AvailableSolutionsChecker;
use ScalexpertPlugin\Helper\FinancingEligibility;
use ScalexpertPlugin\Helper\InsuranceEligibility;
use ScalexpertPlugin\Helper\InsuranceProcess;

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

    public function displayAjaxAddToCart()
    {
        $idCart = Tools::getValue('id_cart');
        $insurance = Tools::getValue('insurance');
//        $params['cart'] = $idCart ? new \Cart((int) $idCart) : \Context::getContext()->cart;
        $params['cart'] = \Context::getContext()->cart;

        $insuranceProductReference = sprintf('%s|%s',
            Tools::getValue('id_product'),
            Tools::getValue('id_product_attribute')
        );

        $_POST['insurances'] = [
            $insuranceProductReference => $insurance
        ];
        $_POST['add'] = true;

        $result = InsuranceProcess::handleCartSave($params);

        $this->ajaxDie(\Tools::jsonEncode(array('hasError' => false, 'content' => $result)));
    }

    private function _getFinancialsForProduct()
    {
        $idProduct = (int)Tools::getValue('idProduct');
        $oProduct = new Product($idProduct);
        if (!Validate::isLoadedObject($oProduct)) {
            return;
        }

        $customizeProduct = FinancingEligibility::getEligibleSolutionByProduct($idProduct);
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

        $idProductAttribute = 0;
        if (version_compare(_PS_VERSION_, '1.7', '>=')) {
            $groups = Tools::getValue('idProductAttribute');

            if (!empty($groups)) {
                $idProductAttribute = (int) Product::getIdProductAttributeByIdAttributes(
                    $idProduct,
                    $groups,
                    true
                );
            }
        } else {
            $idProductAttribute = (int)Tools::getValue('idProductAttribute');
        }

        $customizeProduct = InsuranceEligibility::getEligibleSolutionByProduct($idProduct);
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
                if (!empty($customizeProduct[$eligibleSolution['solutionCode']]['subtitle'])) {
                    $communicationKit['visualDescription'] = $customizeProduct[$eligibleSolution['solutionCode']]['subtitle'];
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

                if (empty($insurancesData)) {
                    continue;
                }

                if(
                    !empty($communicationKit)
                    && is_array($insurancesData)
                ) {
                    $insurancesData = array_merge($communicationKit, $insurancesData);
                }

                $insurancesData['id_product'] = $idProduct;
                $insurancesData['id_product_attribute'] = $idProductAttribute;

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

        $content = '';
        $idCategoryInsurance = Configuration::get(InsuranceProcess::INSURANCE_CATEGORY_CONFIG_NAME);
        foreach ($products as $product) {
            // Skip insurance products
            if (
                !empty($product['id_category_default'])
                && $idCategoryInsurance === $product['id_category_default']
            ) {
                continue;
            }

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

        $customizeProduct = InsuranceEligibility::getEligibleSolutionByProduct($idProduct);
        if (empty($customizeProduct)) {
            return;
        }

        $content = '';
        /*$eligibleSolutions = Insurance::getEligibleSolutionsFromBuyerBillingCountry(
            $buyerCountry
        );*/
        $eligibleSolutions = AvailableSolutionsChecker::getAvailableInsuranceSolutions();

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
                if (!empty($customizeProduct[$eligibleSolution['solutionCode']]['title_cart'])) {
                    $communicationKit['visualTitle'] = $customizeProduct[$eligibleSolution['solutionCode']]['title_cart'];
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

                if (empty($insurancesData)) {
                    continue;
                }

                if(
                    !empty($communicationKit)
                    && is_array($insurancesData)
                ) {
                    $insurancesData = array_merge($communicationKit, $insurancesData);
                }

                $insurancesData['id_product'] = $idProduct;
                $insurancesData['id_product_attribute'] = $idProductAttribute;
                $insurancesData['relatedProduct'] = $eligibleSolution['relatedProduct'];

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
