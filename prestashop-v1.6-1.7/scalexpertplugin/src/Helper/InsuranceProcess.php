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
use PrestaShop\PrestaShop\Adapter\Entity\Cart;
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

        //todo : delete product
        if (\Tools::getIsset('delete')) {
            $idProductDelete = (int) \Tools::getValue('id_product');
            $idProductAttributeDelete = (int) \Tools::getValue('id_product_attribute');

            /*$insuranceProductsToDelete = $this->cartInsuranceRepository->findBy(
                [
                    'idCart' => (int) $cart->id,
                    'idProduct' =>  $idProductDelete,
                    'idProductAttribute' => empty($idProductAttributeDelete) ? null : $idProductAttributeDelete,
                ]
            );*/
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
                    $deleteResult = $oCart->deleteProduct($insuranceProductToDelete['id_insurance_product']);
                }
            }

            /*$deletedInsuranceProduct = CartInsurance::getInsuranceLine(
                $oCart->id,
                $idProductDelete,
                null
            );

            if (!empty($deletedInsuranceProduct)) {
                foreach ($deletedInsuranceProduct as $deletedProduct) {
                    $this->entityManager->remove($deletedProduct);
                }

                $this->entityManager->flush();
            }*/
        }

        //todo : balance product
        self::handleCartQty($oCart);
        //todo : create quote from API if missing
        self::handleQuotationsInCart($oCart);
    }

    private static function handleInsuranceProductsFormSubmit($oCart)
    {
        $insurances = Tools::getValue('insurances');

        if (!empty($insurances)) {
            foreach ($insurances as $productElements => $insurance) {
                $explodedProductElements = explode('|', $productElements);

                $idProduct = isset($explodedProductElements[0]) ? $explodedProductElements[0] : null;
                $idProductAttribute = isset($explodedProductElements[1]) ? $explodedProductElements[1] : null;

                $explodedInsurance = explode('|', $insurance);
                $solutionCode = $explodedInsurance[0] ?? '';
                $idItem = $explodedInsurance[1] ?? '';
                $idInsurance = $explodedInsurance[2] ?? '';

                if (!empty($idProduct) && !empty($idItem) && isset($idInsurance)) {
                    if (empty($idInsurance)) {
                        // Select no insurance choice > delete insurance line if existing.
                        CartInsurance::removeInsuranceLine($oCart->id, $idProduct, $idProductAttribute);
                    } else {
                        $availableInsuranceSolutions = AvailableSolutionsChecker::getAvailableInsuranceSolutions($idProduct, $idProductAttribute);
                        $invalidData = true;

                        if (!empty($availableInsuranceSolutions)) {
                            $availableSolution = reset($availableInsuranceSolutions);

                            if (!empty($availableSolution['insurances']['insurances'])) {
                                foreach ($availableSolution['insurances']['insurances'] as $solutionInsurance) {
                                    if (
                                        $solutionInsurance['id'] == $idInsurance
                                        && $solutionInsurance['itemId'] == $idItem
                                    ) {
                                        $invalidData = false;
                                    }
                                }
                            }
                        }

                        if ($invalidData) {
                            continue;
                        }

                        $cartInsuranceToUpdate = CartInsurance::getInsuranceLine(
                            $oCart->id,
                            $idProduct,
                            $idProductAttribute
                        );

                        $idInsuranceProduct = static::createOrGetInsuranceProduct(
                            $idProduct,
                            $idProductAttribute,
                            $idItem,
                            $idInsurance,
                            $solutionCode
                        );
                        if (!$idInsuranceProduct) {
                            //todo : ERROR.
                            continue;
                        }

                        if (empty($cartInsuranceToUpdate)) {
                            CartInsurance::addInsuranceLine(
                                $oCart->id,
                                $idProduct,
                                $idProductAttribute,
                                $idItem,
                                $idInsurance,
                                $solutionCode,
                                $idInsuranceProduct
                            );
                        } else {
                            CartInsurance::updateInsuranceLine(
                                $cartInsuranceToUpdate['id_cart_insurance'],
                                $oCart->id,
                                $idProduct,
                                $idProductAttribute,
                                $idItem,
                                $idInsurance,
                                $solutionCode,
                                $idInsuranceProduct,
                                null
                            );
                        }
                    }
                }
            }
        }
    }

    private static function createOrGetInsuranceProduct(
        $idProduct,
        $idProductAttribute,
        $idItem,
        $idInsurance,
        $solutionCode
    ) {
        $insuranceProductReference = sprintf('%s|%s', substr($idItem, 0, 18), $idInsurance);

        $productPrice = \Product::getPriceStatic($idProduct, true, $idProductAttribute, 2);
        $insurances = Insurance::searchInsurances($solutionCode, $idItem, $productPrice);

        if (!empty($insurances['insurances'])) {
            foreach ($insurances['insurances'] as $insurance) {
                if (
                    !empty($insurance['id'])
                    && $insurance['id'] == $idInsurance
                ) {
                    $currentInsurance = $insurance;
                }
            }
        }

        $query = new DbQuery();
        $query->select('p.id_product');
        $query->from('product', 'p');
        $query->where('p.reference = \''.pSQL($insuranceProductReference).'\'');
        $insuranceProductId = Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($query);

        if ($insuranceProductId) {
            return (int)$insuranceProductId;
        }

        if (!empty($currentInsurance)) {
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

            if (!empty($insuranceCategoryId)) {
                $insuranceProduct->id_category_default = $insuranceCategoryId;
            }

            $languages = \Language::getLanguages();
            $insuranceProductName = [];
            $insuranceProductLink = [];

            if (!empty($languages)) {
                foreach ($languages as $language) {
                    $name = sprintf(
                        '%s - %s',
                        \Product::getProductName($idProduct, $idProductAttribute, $language['id_lang']),
                        $currentInsurance['description']
                    );
                    $insuranceProductName[$language['id_lang']] = $name;
                    $insuranceProductLink[$language['id_lang']] = Tools::str2url($name);
                }
            }
            $insuranceProduct->name = $insuranceProductName;
            $insuranceProduct->link_rewrite = $insuranceProductLink;

            if ($insuranceProduct->save()) {
                if (!version_compare(_PS_VERSION_, '1.7', '>=')) {
                    // Update price by removing taxe
                    $address = \Address::initialize(null);
                    $id_tax_rules = (int)\Product::getIdTaxRulesGroupByIdProduct($insuranceProduct->id, \Context::getContext());
                    $tax_manager = \TaxManagerFactory::getManager($address, $id_tax_rules);
                    $tax_calculator = $tax_manager->getTaxCalculator();

                    $newPrice = $tax_calculator->removeTaxes($insuranceProduct->price);
                    $insuranceProduct->price = Tools::ps_round((float)$newPrice, 5);
                    $insuranceProduct->update();
                }

                \StockAvailable::setQuantity((int) $insuranceProduct->id, 0, 999999999);

                if (!empty($insuranceCategoryId)) {
                    $insuranceProduct->addToCategories([$insuranceCategoryId]);
                }

                return (int)$insuranceProduct->id;
            }
        }

        return null;
    }

    public static function isInsuranceProduct($idProduct)
    {
        $query = (new DbQuery())->select('id_product')
            ->from('cart_product')
            ->where('id_product = '.(int)$idProduct)
            ->where('id_category = '.(int)Configuration::get(static::INSURANCE_CATEGORY_CONFIG_NAME));

        if (Db::getInstance()->getRow($query)) {
            return true;
        }
        return false;
    }

    public static function createOrderInsurancesSubscriptions($hookParams)
    {
        $order = new \Order($hookParams['id_order']);
        if (!\Validate::isLoadedObject($order)) {
            return;
        }

        if (!\Validate::isLoadedObject($hookParams['newOrderStatus'])) {
            return;
        }

        if ($hookParams['newOrderStatus']->paid) {
            $cartInsurancesProducts = CartInsurance::getInsuranceByIdCart($order->id_cart);
            $cart = new \Cart($order->id_cart);
            $customer = new \Customer($order->id_customer);
            if (
                !\Validate::isLoadedObject($cart)
                || !\Validate::isLoadedObject($customer)
            ) {
                return;
            }

            if (!empty($cartInsurancesProducts)) {
                $cartInsuranceSubscriptions = [];
                foreach ($cartInsurancesProducts as $cartInsurancesProduct) {
                    if ('1' === $cartInsurancesProduct['subscriptions_processed']) {
                        continue;
                    }

                    //@TODO: création + récupération des quotations d'une assurance
                    $quotations = CartInsurance::getInsuranceQuotations($cartInsurancesProduct['id_cart_insurance']);
                    if (!empty($quotations)) {
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

                        CartInsurance::updateSubscriptionsProcessed($cartInsurancesProduct['id_cart_insurance'], true);

                        if (!empty($cartInsuranceSubscriptions)) {
                            //@TODO
                            CartInsurance::updateInsuranceSubscriptions($cartInsurancesProduct['id_cart_insurance'], $cartInsuranceSubscriptions);
//                            $cartInsurancesProduct->setSubscriptions($cartInsuranceSubscriptions);
                        }
                    }
                }
            }
        }
    }

    protected static function handleCartQty($cart)
    {
        try {
            $cartProducts = $cart->getProducts();
        } catch (\Exception $exception) {}

        $addAction = \Tools::getValue('add');
        $qty = (int) \Tools::getValue('qty', 1);
        $idProduct = \Tools::getValue('id_product', 0);
        $cartInsurances = CartInsurance::getInsuranceByIdCart($cart->id);

        if (!empty($cartProducts)) {
            foreach ($cartProducts as $cartProduct) {
                foreach ($cartInsurances as $cartInsurance) {
                    $cartProductAttribute = (isset($cartProduct['id_product_attribute']) && $cartProduct['id_product_attribute'] != 0) ?
                        $cartProduct['id_product_attribute'] : 0;

                    if (
                        $cartInsurance['id_product'] == $cartProduct['id_product']
                        && $cartInsurance['id_product_attribute'] == $cartProductAttribute
                    ) {
                        $insuranceProductQtyInCart = $cart->containsProduct($cartInsurance['id_insurance_product']);

                        if (empty($insuranceProductQtyInCart['quantity'])) {
                            $updateResult = $cart->updateQty(
                                $cartProduct['cart_quantity'],
                                $cartInsurance['id_insurance_product'],
                                null,
                                false,
                                'up'
                            );
                        } else {
                            $qtyDiff = abs($cartProduct['cart_quantity'] - $insuranceProductQtyInCart['quantity']);

                            if ($qtyDiff == 0) {
                                continue;
                            }

                            if ($insuranceProductQtyInCart['quantity'] < $cartProduct['cart_quantity']) {
                                $action = 'up';
                            } else {
                                $action = 'down';
                            }

                            $updateResult = $cart->updateQty(
                                $qtyDiff,
                                $cartInsurance['id_insurance_product'],
                                null,
                                false,
                                $action
                            );
                        }
                    }
                }

                if ($cartProduct['id_category_default'] == \Configuration::get(self::INSURANCE_CATEGORY_CONFIG_NAME)) {
                    $linkedLine = CartInsurance::getInsuranceLineByCartAndInsuranceId($cart->id, $cartProduct['id_product']);

                    if (empty($linkedLine)) {
                        $cart->deleteProduct($cartProduct['id_product']);
                    }
                }
            }
        } elseif (!empty($addAction) && !empty($idProduct) && !empty($qty)) {
            foreach ($cartInsurances as $cartInsurance) {
                if ($cartInsurance['id_product'] == $idProduct) {
                    $updateResult = $cart->updateQty(
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
        $quotationProducts = CartInsurance::getInsuranceByIdCart($cart->id);
        $addAction = \Tools::getValue('add');
        $qty = (int) \Tools::getValue('qty', 1);
        $idProduct = \Tools::getValue('id_product', 0);

        if (!empty($quotationProducts)) {
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

                    $deleteResult = $cart->deleteProduct(
                        $quotationProduct['id_product'],
                        $quotationProduct['id_product_attribute']
                    );
                } elseif (empty($quotations)) {
                    self::adjustQuotationsForCartInsurance(
                        $productInCart['quantity'],
                        'up',
                        $quotationProduct
                    );
                } else {
                    $quotationsCount = count($quotations);

                    if ($productInCart['quantity'] > $quotationsCount) {
                        self::adjustQuotationsForCartInsurance(
                            $productInCart['quantity'] - $quotationsCount,
                            'up',
                            $quotationProduct
                        );
                    } elseif ($productInCart['quantity'] < $quotationsCount) {
                        self::adjustQuotationsForCartInsurance(
                            $quotationsCount - $productInCart['quantity'],
                            'down',
                            $quotationProduct
                        );
                    }
                }
            }
        }
    }

    protected static function adjustQuotationsForCartInsurance(
        $quotationsDiff,
        $operator,
        $cartInsuranceLine
    ) {
        $itemPrice = \Product::getPriceStatic(
            $cartInsuranceLine['id_product'],
            true,
            $cartInsuranceLine['id_product_attribute'],
            2
        );

        $quotations = CartInsurance::getInsuranceQuotations($cartInsuranceLine['id_cart_insurance']);

        if ('up' == $operator) {
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

        //@TODO: liaison quotation >< cartInsuranceLine
        CartInsurance::updateInsuranceQuotations($cartInsuranceLine['id_cart_insurance'], $quotations);
    }
}
