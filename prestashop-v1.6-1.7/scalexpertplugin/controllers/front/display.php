<?php
/**
 * Copyright © Scalexpert.
 * This file is part of Scalexpert plugin for PrestaShop. See COPYING.md for license details.
 *
 * @author    Scalexpert (https://scalexpert.societegenerale.com/)
 * @copyright Scalexpert
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */


use ScalexpertPlugin\Api\Financing;
use ScalexpertPlugin\Api\Insurance;
use ScalexpertPlugin\Helper\AvailableSolutionsChecker;
use ScalexpertPlugin\Helper\FinancingEligibility;
use ScalexpertPlugin\Helper\InsuranceEligibility;
use ScalexpertPlugin\Helper\InsuranceProcess;
use ScalexpertPlugin\Helper\SimulationFormatter;
use ScalexpertPlugin\Service\DataBuilder;
use ScalexpertPlugin\Service\SolutionSorter;

class ScalexpertPluginDisplayModuleFrontController extends ModuleFrontController
{
    public function displayAjaxProduct()
    {
        $type = \Tools::getValue('type');
        if (empty($type)) {
            return;
        }

        $content = '';
        if ('financial' === $type) {
            if ('FR' === strtoupper($this->context->language->iso_code)) {
                $content = $this->_getSimulationForProduct();
            } else {
                $content = $this->_getFinancialsForProduct();
            }
        } elseif ('insurance' === $type) {
            $content = $this->_getInsuranceForProduct();
        }

        $this->ajaxDie(\Tools::jsonEncode([
            'hasError' => false,
            'content' => $content
        ]));
    }

    public function displayAjaxCart()
    {
        $content = $this->_getInsuranceForCart();

        $this->ajaxDie(\Tools::jsonEncode([
            'hasError' => false,
            'content' => $content
        ]));
    }

    public function displayAjaxAddToCart()
    {
        $insurance = Tools::getValue('insurance');
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
            return '';
        }

        $customizeProduct = FinancingEligibility::getEligibleSolutionByProduct($idProduct);
        if (empty($customizeProduct)) {
            return '';
        }

        $eligibleSolutions = Financing::getEligibleSolutionsForFront(
            floor($oProduct->getPrice()),
            strtoupper($this->context->language->iso_code)
        );

        $sortedSolutions = [];
        foreach ($eligibleSolutions as $eligibleSolution) {
            $solutionCode = $eligibleSolution['solutionCode'];

            if (isset($customizeProduct[$solutionCode])) {
                $sortedSolutions[$solutionCode] = $eligibleSolution['communicationKit'];
                $sortedSolutions[$solutionCode]['position'] = $customizeProduct[$solutionCode]['position'];
                $sortedSolutions[$solutionCode]['displayLogo'] = $customizeProduct[$solutionCode]['logo'];
                if (!empty($customizeProduct[$solutionCode]['title'])) {
                    $sortedSolutions[$solutionCode]['visualTitle'] = $customizeProduct[$solutionCode]['title'];
                }
            }
        }

        // Sort products by position
        SolutionSorter::sortSolutionsByPosition($sortedSolutions);

        $content = '';
        foreach ($sortedSolutions as $sortedSolution) {
            $this->context->smarty->assign(
                $this->module->name . '_productButtons',
                [
                    'nameModule' => $this->module->name,
                    'idModal' => md5($sortedSolution['solutionCode'] . '_' . $this->module->name . 'hookDisplayProductButtons')
                ]
            );
            $this->context->smarty->assign(['availableSolution' => $sortedSolution]);

            if (version_compare(_PS_VERSION_, '1.7', '>=')) {
                $content .= $this->context->smarty->fetch(_PS_MODULE_DIR_ . 'scalexpertplugin/views/templates/hook/ps17/productFinancialContent.tpl');
            } else {
                $content .= $this->context->smarty->fetch(_PS_MODULE_DIR_ . 'scalexpertplugin/views/templates/hook/ps16/productFinancialContent.tpl');
            }
        }

        return $content;
    }

    private function _getInsuranceForProduct()
    {
        $idProduct = (int)Tools::getValue('idProduct');
        $oProduct = new Product($idProduct);
        if (!Validate::isLoadedObject($oProduct)) {
            return '';
        }

        $idProductAttribute = 0;
        if (version_compare(_PS_VERSION_, '1.7', '>=')) {
            $groups = Tools::getValue('idProductAttribute');

            if (!empty($groups)) {
                $idProductAttribute = (int)Product::getIdProductAttributeByIdAttributes(
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
            return '';
        }

        $content = '';
        $eligibleSolutions = Insurance::getEligibleSolutionsFromBuyerBillingCountry(
            strtoupper($this->context->language->iso_code)
        );

        foreach ($eligibleSolutions as $eligibleSolution) {
            $solutionCode = $eligibleSolution['solutionCode'];
            if (isset($customizeProduct[$solutionCode])) {
                $this->context->smarty->assign(
                    $this->module->name . '_productButtons',
                    [
                        'nameModule' => $this->module->name,
                        'idModal' => md5(
                            $solutionCode . '_' . $this->module->name . 'hookDisplayProductButtons'
                        )
                    ]
                );

                $content = $this->buildInsuranceContent(
                    $eligibleSolution,
                    $customizeProduct[$solutionCode],
                    $solutionCode,
                    $idProduct,
                    $idProductAttribute,
                    $oProduct->getPrice(true, $idProductAttribute, 2)
                );
            }
        }

        return $content;
    }

    private function _getSimulationForProduct()
    {
        $idProduct = (int)Tools::getValue('idProduct');
        $oProduct = new Product($idProduct);
        if (!Validate::isLoadedObject($oProduct)) {
            return '';
        }

        $customizeProduct = FinancingEligibility::getEligibleSolutionByProduct($idProduct);
        if (empty($customizeProduct)) {
            return '';
        }

        $eligibleSolutions = Financing::getEligibleSolutionsForFront(
            floor($oProduct->getPrice()),
            strtoupper($this->context->language->iso_code)
        );

        $designData = DataBuilder::buildDesignData($eligibleSolutions, $customizeProduct);

        $simulateResponse = Financing::simulateFinancing(
            $oProduct->getPrice(),
            strtoupper($this->context->language->iso_code),
            $designData['solutionCodes']
        );

        $content = '';
        if (
            !$simulateResponse['hasError']
            && isset($simulateResponse['data']['solutionSimulations'])
        ) {
            $simulationsFullData = SimulationFormatter::normalizeSimulations(
                $simulateResponse['data'],
                $designData['designSolutions'],
                true
            );

            $this->context->smarty->assign([
                'solutionSimulations' => $simulationsFullData,
                'financedAmount' => $simulateResponse['data']['financedAmount'],
                'financedAmountFormatted' => \Tools::displayPrice(
                    (float)$simulateResponse['data']['financedAmount']
                ),
            ]);

            if (version_compare(_PS_VERSION_, '1.7', '>=')) {
                $content .= $this->context->smarty->fetch(_PS_MODULE_DIR_ . 'scalexpertplugin/views/templates/hook/ps17/productFinancialSimulationContent.tpl');
            } else {
                $content .= $this->context->smarty->fetch(_PS_MODULE_DIR_ . 'scalexpertplugin/views/templates/hook/ps16/productFinancialSimulationContent.tpl');
            }
        }

        return $content;
    }

    private function _getInsuranceForCart()
    {
        $oCart = Context::getContext()->cart;
        if (!Validate::isLoadedObject($oCart)) {
            return '';
        }

        $products = $oCart->getProducts();
        if (!$products) {
            return '';
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

            $content .= $this->_getInsuranceForProductInCart($product['id_product'], $product['id_product_attribute']);
        }

        return $content;
    }

    private function _getInsuranceForProductInCart($idProduct, $idProductAttribute = 0)
    {
        $oProduct = new Product($idProduct);
        if (!Validate::isLoadedObject($oProduct)) {
            return '';
        }

        $customizeProduct = InsuranceEligibility::getEligibleSolutionByProduct($idProduct);
        if (empty($customizeProduct)) {
            return '';
        }

        $content = '';
        $eligibleSolutions = AvailableSolutionsChecker::getAvailableInsuranceSolutions();

        foreach ($eligibleSolutions as $eligibleSolution) {
            $solutionCode = $eligibleSolution['solutionCode'];
            if (isset($customizeProduct[$solutionCode])) {
                $this->context->smarty->assign(
                    $this->module->name . '_productButtons',
                    [
                        'nameModule' => $this->module->name,
                        'idModal' => md5(
                            $solutionCode . '_' . $this->module->name . 'hookDisplayProductButtons_' . $idProduct . '_' . $idProductAttribute
                        )
                    ]
                );

                $content = $this->buildInsuranceContent(
                    $eligibleSolution,
                    $customizeProduct[$solutionCode],
                    $solutionCode,
                    $idProduct,
                    $idProductAttribute,
                    $oProduct->getPrice(true, $idProductAttribute, 2)
                );
            }
        }

        return $content;
    }

    private function _getInsurancesData(
        $eligibleSolution,
        $customizeProductForSolution,
        $solutionCode,
        $idProduct,
        $idProductAttribute,
        $price
    )
    {
        $insurancesData = [];
        $communicationKit = $eligibleSolution['communicationKit'];

        if (!empty($customizeProductForSolution['title'])) {
            $communicationKit['visualTitle'] = $customizeProductForSolution['title'];
        }
        if (!empty($customizeProductForSolution['subtitle'])) {
            $communicationKit['visualDescription'] = $customizeProductForSolution['subtitle'];
        }
        if (!empty($customizeProductForSolution['title_cart'])) {
            $communicationKit['visualTitle'] = $customizeProductForSolution['title_cart'];
        }
        $communicationKit['displayLogo'] = $customizeProductForSolution['logo'];

        $itemData = Insurance::createItem($solutionCode, $idProduct);
        if (!$itemData) {
            return $insurancesData;
        }

        $insurancesData = Insurance::searchInsurances(
            $solutionCode,
            $itemData['id'],
            $price
        );

        if (empty($insurancesData)) {
            return $insurancesData;
        }

        if (
            !empty($communicationKit)
            && is_array($insurancesData)
        ) {
            $insurancesData = array_merge($communicationKit, $insurancesData);
        }

        $insurancesData['id_product'] = $idProduct;
        $insurancesData['id_product_attribute'] = $idProductAttribute;
        if (!empty($eligibleSolution['relatedProduct'])) {
            $insurancesData['relatedProduct'] = $eligibleSolution['relatedProduct'];
        }

        return $insurancesData;
    }

    /**
     * @param $eligibleSolution
     * @param $customizeProductForSolution
     * @param $solutionCode
     * @param $idProduct
     * @param $idProductAttribute
     * @param $price
     * @return string
     * @throws SmartyException
     */
    private function buildInsuranceContent(
        $eligibleSolution,
        $customizeProductForSolution,
        $solutionCode,
        $idProduct,
        $idProductAttribute,
        $price
    )
    {
        $insurancesData = $this->_getInsurancesData(
            $eligibleSolution,
            $customizeProductForSolution,
            $solutionCode,
            $idProduct,
            $idProductAttribute,
            $price
        );

        $this->context->smarty->assign([
            'insurancesData' => $insurancesData,
        ]);

        if (version_compare(_PS_VERSION_, '1.7', '>=')) {
            $content = $this->context->smarty->fetch(
                _PS_MODULE_DIR_ . 'scalexpertplugin/views/templates/hook/ps17/productInsuranceContent.tpl'
            );
        } else {
            $content = $this->context->smarty->fetch(
                _PS_MODULE_DIR_ . 'scalexpertplugin/views/templates/hook/ps16/productInsuranceContent.tpl'
            );
        }

        return $content;
    }
}
