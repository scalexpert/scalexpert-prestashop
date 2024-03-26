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
use ScalexpertPlugin\Api\Insurance;
use ScalexpertPlugin\Model\CartInsurance;
use Db;
use DbQuery;
use Tools;
use Validate;

class AvailableSolutionsChecker
{
    public static function getAvailableInsuranceSolutions(
        $productID = null,
        $productIDAttribute = null
    )
    {
        $insuranceSolutions = [];
        $buyerBillingCountry = static::getContextBuyerBillingCountry();
        $allInsuranceSolutions = Insurance::getEligibleSolutionsFromBuyerBillingCountry($buyerBillingCountry);

        if (!empty($allInsuranceSolutions)) {
            // Get active configuration
            $activeConfiguration = \Configuration::get('SCALEXPERT_INSURANCE_SOLUTIONS');
            $activeConfiguration = !empty($activeConfiguration) ? json_decode($activeConfiguration, true) : [];

            // Get config configuration
            $designConfiguration = \Configuration::get('SCALEXPERT_CUSTOMIZE_PRODUCT');
            $designConfiguration = !empty($designConfiguration) ? json_decode($designConfiguration, true) : [];

            foreach ($allInsuranceSolutions as $solutionCode => $insuranceSolution) {
                // Case solution is not active in configuration
                if (empty($activeConfiguration[$solutionCode])) {
                    continue;
                }

                if (!isset($designConfiguration[$solutionCode])) {
                    continue;
                }

                if (empty($productID)) {
                    $cart = \Context::getContext()->cart;
                    if (\Validate::isLoadedObject($cart)) {
                        $cartProducts = $cart->getProducts();
                    }

                    if (!empty($cartProducts)) {
                        foreach ($cartProducts as $cartProduct) {
                            $insuranceSolutionData = $insuranceSolution;

                            if (!empty($designConfiguration[$solutionCode]['excludedCategories'])) {
                                // Check category compatibility with current product on product page
                                $productCategories = \Product::getProductCategories($cartProduct['id_product']);

                                if (!empty($productCategories)) {
                                    foreach ($designConfiguration[$solutionCode]['excludedCategories'] as $categoryID) {
                                        // Restricted category for the solution
                                        if (in_array($categoryID, $productCategories)) {
                                            continue 2;
                                        }
                                    }
                                }
                            }

                            if (!empty($designConfiguration[$solutionCode]['excludedProducts'])) {
                                $excludedProducts = explode(',', $designConfiguration[$solutionCode]['excludedProducts']);

                                // Restricted products for the solution
                                if (in_array($cartProduct['id_product'], $excludedProducts)) {
                                    continue;
                                }
                            }

                            $itemData = Insurance::createItem($solutionCode, $cartProduct['id_product']);

                            if (isset($itemData['id'])) {
                                $itemId = $itemData['id'];
                                $insuranceSolutionData['itemId'] = $itemId;

                                $productPrice = \Product::getPriceStatic(
                                    $cartProduct['id_product'],
                                    true,
                                    $cartProduct['id_product_attribute'],
                                    2
                                );

                                $insurances = Insurance::searchInsurances($solutionCode, $itemId, $productPrice);

                                if (!empty($insurances)) {
                                    $insuranceSolutionData['insurances'] = $insurances;
                                }
                            }

                            if (empty($insuranceSolutionData['insurances'])) {
                                continue;
                            }

                            $insuranceSolutionData['relatedProduct'] = $cartProduct;
                            $insuranceSolutionData['designConfiguration'] = $designConfiguration[$solutionCode];
                            $insuranceSolutions[] = $insuranceSolutionData;
                        }
                    }
                } else {
                    $insuranceSolutionData = $insuranceSolution;

                    if (!empty($designConfiguration[$solutionCode]['excludedCategories'])) {
                        // Check category compatibility with current product on product page
                        $productCategories = \Product::getProductCategories($productID);

                        if (!empty($productCategories)) {
                            foreach ($designConfiguration[$solutionCode]['excludedCategories'] as $categoryID) {
                                // Restricted category for the solution
                                if (in_array($categoryID, $productCategories)) {
                                    continue 2;
                                }
                            }
                        }
                    }

                    if (!empty($designConfiguration[$solutionCode]['excludedProducts'])) {
                        $excludedProducts = explode(',', $designConfiguration[$solutionCode]['excludedProducts']);

                        // Restricted products for the solution
                        if (in_array($productID, $excludedProducts)) {
                            continue;
                        }
                    }

                    $itemData = Insurance::createItem($solutionCode, $productID);

                    if (isset($itemData['id'])) {
                        $itemId = $itemData['id'];
                        $insuranceSolutionData['itemId'] = $itemId;

                        $productPrice = \Product::getPriceStatic($productID, true, $productIDAttribute, 2);
                        $insurances = Insurance::searchInsurances($solutionCode, $itemId, $productPrice);

                        if (!empty($insurances)) {
                            $insuranceSolutionData['insurances'] = $insurances;
                            $insuranceSolutionData['productID'] = $productID;
                        }
                    }

                    if (empty($insuranceSolutionData['insurances'])) {
                        continue;
                    }

                    $insuranceSolutionData['designConfiguration'] = $designConfiguration[$solutionCode];
                    $insuranceSolutions[] = $insuranceSolutionData;
                }
            }
        }

        return $insuranceSolutions;
    }

    protected static function getContextBuyerBillingCountry()
    {
        $cart = \Context::getContext()->cart;
        if (
            \Validate::isLoadedObject($cart)
            && !empty($cart->id_address_invoice)
        ) {
            $addressInvoice = new \Address((int) $cart->id_address_invoice);

            if (\Validate::isLoadedObject($addressInvoice) && !empty($addressInvoice->id_country)) {
                $buyerBillingCountry = \Country::getIsoById($addressInvoice->id_country);
            }
        }

        if (empty($buyerBillingCountry)) {
            $buyerBillingCountry = \Context::getContext()->language->iso_code;
        }

        return $buyerBillingCountry;
    }
}
