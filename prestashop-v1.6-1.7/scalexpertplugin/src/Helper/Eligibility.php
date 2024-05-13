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
        $oProduct = new \Product($idProduct);
        if (!Validate::isLoadedObject($oProduct)) {
            return [];
        }

        $financingSolutions = static::getConfigSolutions();
        $jsonCustomizeProduct = Configuration::get('SCALEXPERT_CUSTOMIZE_PRODUCT');
        $customizeProduct = json_decode($jsonCustomizeProduct, true);

        foreach ($customizeProduct as $solutionCode => $params) {
            if (empty($financingSolutions[$solutionCode])) {
                unset($customizeProduct[$solutionCode]);
            }
            if (empty($params['display'])) {
                unset($customizeProduct[$solutionCode]);
            }

            if (!empty($params['excludedCategories'])) {
                $productCategories = $oProduct->getCategories();

                foreach ($params['excludedCategories'] as $categoryID) {
                    // Restricted category for the solution
                    if (in_array($categoryID, $productCategories)) {
                        unset($customizeProduct[$solutionCode]);
                        break;
                    }
                }
            }

            if (!empty($params['excludedProducts'])) {
                $excludedProducts = explode(',', $params['excludedProducts']);

                // Restricted products for the solution
                if (in_array($idProduct, $excludedProducts)) {
                    unset($customizeProduct[$solutionCode]);
                }
            }
        }

        return $customizeProduct;
    }

    /**
     * @param \Cart $oCart
     * @return array|mixed
     * @throws \PrestaShopDatabaseException
     */
    public static function getEligibleSolutionByCart($oCart)
    {
        if (!Validate::isLoadedObject($oCart)) {
            return [];
        }

        foreach ($oCart->getProducts() as $product) {
            if ($product['id_category_default'] == (int)Configuration::get('SCALEXPERT_INSURANCE_CATEGORY')) {
                return [];
            }
        }

        $financingSolutions = static::getConfigSolutions();
        $jsonCustomizeProduct = Configuration::get('SCALEXPERT_CUSTOMIZE_PRODUCT');
        $customizeProduct = json_decode($jsonCustomizeProduct, true);

        foreach ($customizeProduct as $solutionCode => $params) {
            if (empty($financingSolutions[$solutionCode])) {
                unset($customizeProduct[$solutionCode]);
                continue;
            }

            $ids = [];
            foreach ($oCart->getProducts() as $product) {
                $ids[] = $product['id_product'];
            }

            if (!empty($params['excludedCategories'])) {
                $query = (new DbQuery())
                    ->select('cp.id_category')
                    ->from('category_product', 'cp')
                    ->where('id_product IN ('.implode(',', $ids).')');
                $productCategories = \Db::getInstance()->executeS($query);

                if ($productCategories) {
                    // Extract category ids for in_array check
                    $categoryIds = [];
                    foreach ($productCategories as $category) {
                        $categoryIds[] = $category['id_category'];
                    }

                    foreach ($params['excludedCategories'] as $categoryID) {
                        // Restricted category for the solution
                        if (in_array($categoryID, $categoryIds)) {
                            unset($customizeProduct[$solutionCode]);
                            continue 2;
                        }
                    }
                }
            }

            if (!empty($params['excludedProducts'])) {
                $excludedProducts = explode(',', $params['excludedProducts']);

                foreach ($ids as $id) {
                    // Restricted products for the solution
                    if (in_array($id, $excludedProducts)) {
                        unset($customizeProduct[$solutionCode]);
                        continue 2;
                    }
                }
            }
        }

        return $customizeProduct;
    }
}
