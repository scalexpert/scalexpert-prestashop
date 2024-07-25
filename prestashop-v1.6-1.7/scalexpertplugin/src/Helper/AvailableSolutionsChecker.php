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

use ScalexpertPlugin\Api\Insurance;

class AvailableSolutionsChecker
{
    public static function getAvailableInsuranceSolutions(
        $productID = null,
        $productIDAttribute = null
    )
    {
        $productList = [];
        $insuranceSolutions = [];

        $allInsuranceSolutions = Insurance::getEligibleSolutionsFromBuyerBillingCountry(
            \Context::getContext()->language->iso_code
        );
        if (empty($allInsuranceSolutions)) {
            return [];
        }

        $productList[] = [
            'id_product' => $productID,
            'id_product_attribute' => $productIDAttribute,
            'name' => ''
        ];
        if (empty($productID)) {
            $cart = \Context::getContext()->cart;
            if (\Validate::isLoadedObject($cart)) {
                $productList = $cart->getProducts();
            }
        }

        // Get active configuration
        $activeConfiguration = \Configuration::get('SCALEXPERT_INSURANCE_SOLUTIONS');
        $activeConfiguration = !empty($activeConfiguration) ? json_decode($activeConfiguration, true) : [];

        // Get config configuration
        $designConfiguration = \Configuration::get('SCALEXPERT_CUSTOMIZE_PRODUCT');
        $designConfiguration = !empty($designConfiguration) ? json_decode($designConfiguration, true) : [];

        foreach ($allInsuranceSolutions as $solutionCode => $insuranceSolution) {
            // Case solution is not active in configuration
            if (
                empty($activeConfiguration[$solutionCode])
                || !isset($designConfiguration[$solutionCode])
            ) {
                continue;
            }

            foreach ($productList as $product) {
                if (Eligibility::belongsToExclusions(
                    $product['id_product'],
                    $designConfiguration[$solutionCode]['excludedCategories'],
                    $designConfiguration[$solutionCode]['excludedProducts']
                )) {
                    continue;
                }

                $insuranceSolutionData = $insuranceSolution;
                $itemData = Insurance::createItem($solutionCode, $product['id_product']);

                if (isset($itemData['id'])) {
                    $itemId = $itemData['id'];
                    $insuranceSolutionData['itemId'] = $itemId;

                    $productPrice = \Product::getPriceStatic(
                        $product['id_product'],
                        true,
                        $product['id_product_attribute'],
                        2
                    );

                    $insurances = Insurance::searchInsurances($solutionCode, $itemId, $productPrice);

                    if (!empty($insurances)) {
                        $insuranceSolutionData['insurances'] = $insurances;
                        $insuranceSolutionData['productID'] = $product['id_product'];
                    }
                }

                if (empty($insuranceSolutionData['insurances'])) {
                    continue;
                }

                $insuranceSolutionData['relatedProduct'] = $product;
                $insuranceSolutionData['designConfiguration'] = $designConfiguration[$solutionCode];
                $insuranceSolutions[] = $insuranceSolutionData;
            }
        }

        return $insuranceSolutions;
    }
}
