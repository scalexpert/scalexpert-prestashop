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

class InsuranceProcess
{
    const INSURANCE_CATEGORY_CONFIG_NAME = 'SCALEXPERT_INSURANCE_CATEGORY';

    private static $inProgress = false;

    public static function handleCartSave($params)
    {
        $oCart = $params['cart'] ?? null;
        if (!Validate::isLoadedObject($oCart)) {
            return;
        }

        if (static::$inProgress) {
            return;
        }
        static::$inProgress = true;

        self::handleInsuranceProductsFormSubmit($oCart);

        if (\Tools::getIsset('delete')) {
            $idProductDelete = (int)\Tools::getValue('id_product');
            $idProductAttributeDelete = (int)\Tools::getValue('id_product_attribute');

            $insuranceProductsToDelete = CartInsurance::getInsuranceLine(
                $oCart->id,
                $idProductDelete,
                empty($idProductAttributeDelete) ? null : $idProductAttributeDelete
            );

            if (!empty($insuranceProductsToDelete)) {
                foreach ($insuranceProductsToDelete as $insuranceProductToDelete) {
                    CartInsurance::removeInsuranceLine(
                        $insuranceProductToDelete['id_cart'],
                        $insuranceProductToDelete['id_product'],
                        $insuranceProductToDelete['id_product_attribute']
                    );
                    $oCart->deleteProduct($insuranceProductToDelete['id_insurance_product']);
                }
            }
        }

        static::handleCartQty($oCart);
        static::handleQuotationsInCart($oCart);
    }

    private static function handleInsuranceProductsFormSubmit($oCart)
    {
        $insurances = Tools::getValue('insurances');
        if (empty($insurances)) {
            return;
        }

        foreach ($insurances as $productElements => $insurance) {
            $explodedProductElements = explode('|', $productElements);

            $idProduct = $explodedProductElements[0] ?? null;
            $idProductAttribute = $explodedProductElements[1] ?? null;

            $explodedInsurance = explode('|', $insurance);
            $solutionCode = $explodedInsurance[0] ?? '';
            $idItem = $explodedInsurance[1] ?? '';
            $idInsurance = $explodedInsurance[2] ?? '';

            if (
                empty($idProduct)
                || empty($idItem)
            ) {
                return;
            }

            if (empty($idInsurance)) {
                // Select no insurance choice > delete insurance line if existing.
                CartInsurance::removeInsuranceLine($oCart->id, $idProduct, $idProductAttribute);
                continue;
            }

            $availableInsuranceSolutions = AvailableSolutionsChecker::getAvailableInsuranceSolutions(
                $idProduct,
                $idProductAttribute
            );
            if (static::checkInsuranceValidData(
                $availableInsuranceSolutions,
                (int)$idInsurance,
                (int)$idItem
            )) {
                continue;
            }

            $idInsuranceProduct = static::createOrGetInsuranceProduct(
                $idProduct,
                $idProductAttribute,
                $idItem,
                $idInsurance,
                $solutionCode
            );
            if (!$idInsuranceProduct) {
                continue;
            }

            $cartInsuranceToUpdate = CartInsurance::getInsuranceLine(
                $oCart->id,
                $idProduct,
                $idProductAttribute
            );
            if (empty($cartInsuranceToUpdate)) {
                CartInsurance::addInsuranceLine(
                    [
                        'idCart' => $oCart->id,
                        'idProduct' => $idProduct,
                        'idProductAttribute' => $idProductAttribute,
                        'idItem' => $idItem,
                        'idInsurance' => $idInsurance,
                        'solutionCode' => $solutionCode,
                        'idInsuranceProduct' => $idInsuranceProduct
                    ]
                );
            } else {
                CartInsurance::updateInsuranceLine(
                    $cartInsuranceToUpdate['id_cart_insurance'],
                    [
                        'idCart' => $oCart->id,
                        'idProduct' => $idProduct,
                        'idProductAttribute' => $idProductAttribute,
                        'idItem' => $idItem,
                        'idInsurance' => $idInsurance,
                        'solutionCode' => $solutionCode,
                        'idInsuranceProduct' => $idInsuranceProduct
                    ]
                );
            }
        }
    }

    private static function checkInsuranceValidData(
        $availableInsuranceSolutions,
        $insuranceId,
        $itemId
    ): bool
    {
        if (empty($availableInsuranceSolutions)) {
            return false;
        }

        $availableSolution = reset($availableInsuranceSolutions);
        if (empty($availableSolution['insurances']['insurances'])) {
            return false;
        }

        foreach ($availableSolution['insurances']['insurances'] as $solutionInsurance) {
            if (
                (int)$solutionInsurance['id'] === $insuranceId
                && (int)$solutionInsurance['itemId'] === $itemId
            ) {
                return false;
            }
        }

        return true;
    }

    private static function createOrGetInsuranceProduct(
        $idProduct,
        $idProductAttribute,
        $idItem,
        $idInsurance,
        $solutionCode
    )
    {
        $insuranceProductReference = sprintf('%s|%s', substr($idItem, 0, 18), $idInsurance);

        $query = new DbQuery();
        $query->select('p.id_product');
        $query->from('product', 'p');
        $query->where('p.reference = \'' . pSQL($insuranceProductReference) . '\'');

        $insuranceProductId = Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($query);
        if ($insuranceProductId) {
            return (int)$insuranceProductId;
        }

        $productPrice = \Product::getPriceStatic($idProduct, true, $idProductAttribute, 2);
        $insurances = Insurance::searchInsurances($solutionCode, $idItem, $productPrice);
        if (empty($insurances['insurances'])) {
            return null;
        }

        foreach ($insurances['insurances'] as $insurance) {
            if (
                !empty($insurance['id'])
                && (int)$insurance['id'] === (int)$idInsurance
            ) {
                $currentInsurance = $insurance;
            }
        }
        if (empty($currentInsurance)) {
            return null;
        }

        $insuranceProduct = new \Product();
        $insuranceProduct->reference = $insuranceProductReference;
        $insuranceProduct->is_virtual = true;
        $insuranceProduct->active = true;
        $insuranceProduct->visibility = 'none';
        $insuranceProduct->available_for_order = true;
        if (version_compare(_PS_VERSION_, '1.7', '>=')) {
            $insuranceProduct->delivery_out_stock = true;
        } else {
            $insuranceProduct->out_of_stock = 1;
        }
        $insuranceProduct->price = (float)$currentInsurance['price'];
        $insuranceCategoryId = (int)Configuration::get(static::INSURANCE_CATEGORY_CONFIG_NAME);
        $insuranceProduct->id_category_default = $insuranceCategoryId;

        $insuranceProductName = [];
        $insuranceProductLink = [];
        $languages = \Language::getLanguages();
        foreach ($languages as $language) {
            $name = sprintf(
                '%s - %s',
                \Product::getProductName($idProduct, $idProductAttribute, $language['id_lang']),
                $currentInsurance['description']
            );
            $insuranceProductName[$language['id_lang']] = $name;
            $insuranceProductLink[$language['id_lang']] = Tools::str2url($name);
        }
        $insuranceProduct->name = $insuranceProductName;
        $insuranceProduct->link_rewrite = $insuranceProductLink;

        try {
            $insuranceProduct->save();
            $insuranceProduct->addToCategories([$insuranceCategoryId]);
            \StockAvailable::setQuantity((int)$insuranceProduct->id, 0, 999999999);
        } catch (\Exception $e) {
            \PrestaShopLogger::addLog($e->getMessage());
            return null;
        }

        if (!version_compare(_PS_VERSION_, '1.7', '>=')) {
            // Update price by removing taxe
            $address = \Address::initialize(null);
            $id_tax_rules = (int)\Product::getIdTaxRulesGroupByIdProduct(
                $insuranceProduct->id,
                \Context::getContext()
            );
            $tax_manager = \TaxManagerFactory::getManager($address, $id_tax_rules);
            $tax_calculator = $tax_manager->getTaxCalculator();

            $newPrice = $tax_calculator->removeTaxes($insuranceProduct->price);
            $insuranceProduct->price = Tools::ps_round((float)$newPrice, 5);
            $insuranceProduct->update();
        }

        // Create ProductDownload linked to InsuranceProduct in order to make it virtual product
        $productDownload = new \ProductDownload();
        $productDownload->id_product = $insuranceProduct->id;
        try {
            $productDownload->save();
        } catch (\Exception $e) {
            \PrestaShopLogger::addLog($e->getMessage());
        }

        return (int)$insuranceProduct->id;
    }

    public static function isInsuranceProduct($idProduct)
    {
        $query = (new DbQuery())->select('id_product')
            ->from('cart_product')
            ->where('id_product = ' . (int)$idProduct)
            ->where('id_category = ' . (int)Configuration::get(static::INSURANCE_CATEGORY_CONFIG_NAME));

        if (\Db::getInstance()->getRow($query)) {
            return true;
        }
        return false;
    }

    public static function createOrderInsurancesSubscriptions($hookParams)
    {
        $order = new \Order($hookParams['id_order']);
        if (
            !\Validate::isLoadedObject($order)
            || !\Validate::isLoadedObject($hookParams['newOrderStatus'])
            || !$hookParams['newOrderStatus']->paid
        ) {
            return;
        }

        $cartInsurancesProducts = CartInsurance::getInsuranceByIdCart($order->id_cart);
        $cart = new \Cart($order->id_cart);
        $customer = new \Customer($order->id_customer);
        if (
            !\Validate::isLoadedObject($cart)
            || !\Validate::isLoadedObject($customer)
            || empty($cartInsurancesProducts)
        ) {
            return;
        }

        $cartInsuranceSubscriptions = [];
        foreach ($cartInsurancesProducts as $cartInsurancesProduct) {
            if ('1' === $cartInsurancesProduct['subscriptions_processed']) {
                continue;
            }

            $quotations = CartInsurance::getInsuranceQuotations($cartInsurancesProduct['id_cart_insurance']);
            if (empty($quotations)) {
                continue;
            }

            foreach ($quotations as $quoteData) {
                $insuranceSubscription = Insurance::createInsuranceSubscription(
                    $quoteData,
                    $cartInsurancesProduct,
                    $cart,
                    $order,
                    $customer
                );

                if (!empty($insuranceSubscription['insuranceSubscriptionId'])) {
                    $cartInsuranceSubscriptions[] = $insuranceSubscription['insuranceSubscriptionId'];
                }
            }

            CartInsurance::updateSubscriptionsProcessed(
                $cartInsurancesProduct['id_cart_insurance'],
                true
            );

            if (!empty($cartInsuranceSubscriptions)) {
                CartInsurance::updateInsuranceSubscriptions(
                    $cartInsurancesProduct['id_cart_insurance'],
                    $cartInsuranceSubscriptions
                );
            }
        }
    }

    protected static function handleCartQty($cart)
    {
        $addAction = \Tools::getValue('add');
        $qty = (int)\Tools::getValue('qty', 1);
        $idProduct = (int)\Tools::getValue('id_product', 0);

        $cartInsurances = CartInsurance::getInsuranceByIdCart($cart->id);
        try {
            $cartProducts = $cart->getProducts();
        } catch (\Exception $exception) {
            $cartProducts = [];
        }

        if (!empty($cartProducts)) {
            foreach ($cartProducts as $cartProduct) {
                foreach ($cartInsurances as $cartInsurance) {
                    $cartProductAttribute = (
                        isset($cartProduct['id_product_attribute'])
                        && (int)$cartProduct['id_product_attribute'] !== 0
                    ) ? (int)$cartProduct['id_product_attribute'] : 0;

                    if (
                        (int)$cartInsurance['id_product'] === (int)$cartProduct['id_product']
                        && (int)$cartInsurance['id_product_attribute'] === $cartProductAttribute
                    ) {
                        $insuranceProductQtyInCart = $cart->containsProduct($cartInsurance['id_insurance_product']);

                        if (empty($insuranceProductQtyInCart['quantity'])) {
                            $cart->updateQty(
                                $cartProduct['cart_quantity'],
                                $cartInsurance['id_insurance_product'],
                                null,
                                false,
                                'up'
                            );
                        } else {
                            $qtyDiff = abs($cartProduct['cart_quantity'] - $insuranceProductQtyInCart['quantity']);

                            if ((int)$qtyDiff === 0) {
                                continue;
                            }

                            if ($insuranceProductQtyInCart['quantity'] < $cartProduct['cart_quantity']) {
                                $action = 'up';
                            } else {
                                $action = 'down';
                            }

                            $cart->updateQty(
                                $qtyDiff,
                                $cartInsurance['id_insurance_product'],
                                null,
                                false,
                                $action
                            );
                        }
                    }
                }

                if ((int)$cartProduct['id_category_default'] === (int)\Configuration::get(self::INSURANCE_CATEGORY_CONFIG_NAME)) {
                    $linkedLine = CartInsurance::getInsuranceLineByCartAndInsuranceId(
                        $cart->id,
                        $cartProduct['id_product']
                    );

                    if (empty($linkedLine)) {
                        $cart->deleteProduct($cartProduct['id_product'], $cartProduct['id_product_attribute']);
                    }
                }
            }

            return;
        }

        if (
            !empty($addAction)
            && !empty($idProduct)
            && !empty($qty)
        ) {
            foreach ($cartInsurances as $cartInsurance) {
                if ((int)$cartInsurance['id_product'] === $idProduct) {
                    $cart->updateQty(
                        $qty,
                        $cartInsurance['id_insurance_product'],
                        null,
                        false,
                        'up'
                    );
                }
            }
        }
    }

    protected static function handleQuotationsInCart($cart)
    {
        $addAction = \Tools::getValue('add');
        $qty = (int)\Tools::getValue('qty', 1);
        $idProduct = (int)\Tools::getValue('id_product', 0);

        $quotationProducts = CartInsurance::getInsuranceByIdCart($cart->id);
        if (empty($quotationProducts)) {
            return;
        }

        foreach ($quotationProducts as $quotationProduct) {
            $productInCart = $cart->containsProduct(
                $quotationProduct['id_product'],
                $quotationProduct['id_product_attribute']
            );

            if (
                empty($productInCart['quantity'])
                && !empty($addAction)
                && $idProduct == $quotationProduct['id_product']
            ) {
                $productInCart['quantity'] = $qty;
            }

            $quotations = CartInsurance::getInsuranceQuotations($quotationProduct['id_cart_insurance']);

            if (empty($productInCart['quantity'])) {
                CartInsurance::removeInsuranceLine(
                    $quotationProduct['id_cart'],
                    $quotationProduct['id_product'],
                    $quotationProduct['id_product_attribute']
                );

                $cart->deleteProduct(
                    $quotationProduct['id_product'],
                    $quotationProduct['id_product_attribute']
                );
            } elseif (empty($quotations)) {
                static::adjustQuotationsForCartInsurance(
                    $productInCart['quantity'],
                    'up',
                    $quotationProduct
                );
            } else {
                $quotationsCount = count($quotations);

                if ($productInCart['quantity'] > $quotationsCount) {
                    static::adjustQuotationsForCartInsurance(
                        $productInCart['quantity'] - $quotationsCount,
                        'up',
                        $quotationProduct
                    );
                } elseif ($productInCart['quantity'] < $quotationsCount) {
                    static::adjustQuotationsForCartInsurance(
                        $quotationsCount - $productInCart['quantity'],
                        'down',
                        $quotationProduct
                    );
                }
            }
        }
    }

    protected static function adjustQuotationsForCartInsurance(
        $quotationsDiff,
        $operator,
        $cartInsuranceLine
    )
    {
        $itemPrice = \Product::getPriceStatic(
            $cartInsuranceLine['id_product'],
            true,
            $cartInsuranceLine['id_product_attribute'],
            2
        );

        $quotations = CartInsurance::getInsuranceQuotations($cartInsuranceLine['id_cart_insurance']);

        if ('up' === $operator) {
            for ($quote = 0; $quote < $quotationsDiff; $quote++) {

                $quoteData = Insurance::createInsuranceQuotation(
                    $cartInsuranceLine['solution_code'],
                    $cartInsuranceLine['id_item'],
                    $itemPrice,
                    $cartInsuranceLine['id_insurance']
                );

                $quotations[] = $quoteData;
            }
        } else {
            $quotations = array_slice($quotations, 0, count($quotations) - $quotationsDiff);
        }

        CartInsurance::updateInsuranceQuotations($cartInsuranceLine['id_cart_insurance'], $quotations);
    }
}
