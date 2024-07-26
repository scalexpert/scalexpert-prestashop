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

use Configuration;
use DbQuery;
use Validate;

class Eligibility
{
    protected static function getConfigSolutions()
    {
        return [];
    }

    public static function getEligibleSolutionByProduct($idProduct)
    {
        $financingSolutions = static::getConfigSolutions();
        $jsonCustomizeProduct = Configuration::get('SCALEXPERT_CUSTOMIZE_PRODUCT');
        $customizeProduct = json_decode($jsonCustomizeProduct, true);

        if (
            !is_array($customizeProduct)
            || empty($customizeProduct)
        ) {
            return [];
        }

        foreach ($customizeProduct as $solutionCode => $params) {
            if (
                empty($financingSolutions[$solutionCode])
                || empty($params['display'])
            ) {
                unset($customizeProduct[$solutionCode]);
            }

            if (static::belongsToExclusions(
                $idProduct,
                !empty($params['excludedCategories']) ? $params['excludedCategories'] : [],
                !empty($params['excludedProducts']) ? $params['excludedProducts'] : []
            )) {
                unset($customizeProduct[$solutionCode]);
            }
        }

        return $customizeProduct;
    }

    /**
     * @param \Cart $oCart
     * @return array
     * @throws \PrestaShopDatabaseException
     */
    public static function getEligibleSolutionByCart($oCart): array
    {
        if (!Validate::isLoadedObject($oCart)) {
            return [];
        }

        $ids = [];
        foreach ($oCart->getProducts() as $product) {
            if ((int)$product['id_category_default'] === (int)Configuration::get('SCALEXPERT_INSURANCE_CATEGORY')) {
                return [];
            }
            $ids[] = $product['id_product'];
        }

        $financingSolutions = static::getConfigSolutions();
        $jsonCustomizeProduct = Configuration::get('SCALEXPERT_CUSTOMIZE_PRODUCT');
        $customizeProduct = json_decode($jsonCustomizeProduct, true);

        if (
            !is_array($customizeProduct)
            || empty($customizeProduct)
        ) {
            return [];
        }

        foreach ($customizeProduct as $solutionCode => $params) {
            if (empty($financingSolutions[$solutionCode])) {
                unset($customizeProduct[$solutionCode]);
                continue;
            }

            foreach ($ids as $id) {
                if (static::belongsToExclusions(
                    $id,
                    !empty($params['excludedCategories']) ? $params['excludedCategories'] : [],
                    !empty($params['excludedProducts']) ? $params['excludedProducts'] : []
                )) {
                    unset($customizeProduct[$solutionCode]);
                    continue 2;
                }
            }
        }

        return $customizeProduct;
    }

    public static function belongsToExclusions(
        $productId,
        $excludedCategories,
        $excludedProducts
    ): bool
    {
        if (!empty($excludedCategories)) {
            // Check category compatibility with current product on product page
            $productCategories = \Product::getProductCategories($productId);

            if (!empty($productCategories)) {
                foreach ($excludedCategories as $categoryId) {
                    // Restricted category for the solution
                    if (in_array($categoryId, $productCategories)) {
                        return true;
                    }
                }
            }
        }

        if (!empty($excludedProducts)) {
            $products = explode(
                ',',
                $excludedProducts
            );

            // Restricted products for the solution
            if (in_array($productId, $products)) {
                return true;
            }
        }

        return false;
    }
}
