<?php
/**
 * Copyright © Scalexpert.
 * This file is part of Scalexpert plugin for PrestaShop.
 *
 * @author    Société Générale
 * @copyright Scalexpert
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
        }

        return $customizeProduct;
    }

    /**
     * @param \Cart $oCart
     * @return array|mixed
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

            if (!empty($params['excludedCategories'])) {
                $ids = [];
                foreach ($oCart->getProducts() as $product) {
                    $ids[] = $product['id_product'];
                }
                $query = (new DbQuery())->select('cp.id_category')
                    ->from('category_product', 'cp')
                    ->where('id_product IN ('.implode(',', $ids).')');
                $productCategories = \Db::getInstance()->executeS($query);

                if ($productCategories) {
                    foreach ($params['excludedCategories'] as $categoryID) {
                        // Restricted category for the solution
                        if (in_array($categoryID, $productCategories)) {
                            unset($customizeProduct[$solutionCode]);
                            break;
                        }
                    }
                }
            }
        }

        return $customizeProduct;
    }
}
