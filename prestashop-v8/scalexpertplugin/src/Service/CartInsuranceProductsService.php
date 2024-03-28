<?php
/**
 * Copyright © Scalexpert.
 * This file is part of Scalexpert plugin for PrestaShop. See COPYING.md for license details.
 *
 * @author    Scalexpert (https://scalexpert.societegenerale.com/)
 * @copyright Scalexpert
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

declare(strict_types=1);

namespace ScalexpertPlugin\Service;

use PrestaShop\PrestaShop\Core\ConfigurationInterface;
use ScalexpertPlugin\Entity\ScalexpertCartInsurance;
use ScalexpertPlugin\Helper\API\Client;

class CartInsuranceProductsService
{
    private $apiClient;

    private $entityManager;

    private $availableSolutions;

    private $configuration;

    private $cartInsuranceRepository;

    const CONFIGURATION_INSURANCE_PRODUCTS_CATEGORY = 'SCALEXPERT_INSURANCE_PRODUCTS_CATEGORY';

    private $inProgress;

    public function __construct(Client $apiClient, $entityManager, $availableSolutions, ConfigurationInterface $configuration)
    {
        $this->apiClient = $apiClient;
        $this->entityManager = $entityManager;
        $this->availableSolutions = $availableSolutions;
        $this->configuration = $configuration;
        $this->cartInsuranceRepository = $this->entityManager->getRepository(ScalexpertCartInsurance::class);
        $this->inProgress = false;
    }

    public function handleCartSave($cart)
    {
        if ($this->inProgress) {
            return;
        }

        $this->inProgress = true;

        $this->handleInsuranceProductsFormSubmit($cart);

        if (\Tools::getIsset('delete')) {
            $idProductDelete = (int) \Tools::getValue('id_product');
            $idProductAttributeDelete = (int) \Tools::getValue('id_product_attribute');

            $insuranceProductsToDelete = $this->cartInsuranceRepository->findBy(
                [
                    'idCart' => (int) $cart->id,
                    'idProduct' =>  $idProductDelete,
                    'idProductAttribute' => empty($idProductAttributeDelete) ? null : $idProductAttributeDelete,
                ]
            );

            if (!empty($insuranceProductsToDelete)) {
                foreach ($insuranceProductsToDelete as $insuranceProductToDelete) {
                    $this->entityManager->remove($insuranceProductToDelete);
                    $this->entityManager->flush();
                    $cart->deleteProduct($insuranceProductToDelete->getIdInsuranceProduct());
                }
            }

            $deletedInsuranceProduct = $this->cartInsuranceRepository->findBy(
                [
                    'idCart' => (int) $cart->id,
                    'idInsuranceProduct' => $idProductDelete,
                ]
            );

            if (!empty($deletedInsuranceProduct)) {
                foreach ($deletedInsuranceProduct as $deletedProduct) {
                    $this->entityManager->remove($deletedProduct);
                }

                $this->entityManager->flush();
            }
        }

        $this->handleCartQty($cart);
        $this->handleQuotationsInCart($cart);
    }

    public function handleInsuranceProductsFormSubmit($cart)
    {
        $insurances = \Tools::getValue('insurances');

        if (!empty($insurances)) {
            foreach ($insurances as $productElements => $insurance) {
                $explodedProductElements = explode('|', $productElements);

                $idProduct = !empty($explodedProductElements[0]) ? $explodedProductElements[0] : null;
                $idProductAttribute = !empty($explodedProductElements[1]) ? $explodedProductElements[1] : null;

                $explodedInsurance = explode('|', $insurance);
                $solutionCode = $explodedInsurance[0] ?? '';
                $idItem = $explodedInsurance[1] ?? '';
                $idInsurance = $explodedInsurance[2] ?? '';

                if (!empty($idProduct) && !empty($idItem) && isset($idInsurance)) {
                    if (empty($idInsurance)) {
                        $cartInsurancesToDelete = $this->cartInsuranceRepository->findBy([
                            'idCart' => (int) $cart->id,
                            'idProduct' => $idProduct,
                            'idProductAttribute' => $idProductAttribute,
                        ]);

                        if (!empty($cartInsurancesToDelete)) {
                            foreach ($cartInsurancesToDelete as $cartInsuranceToDelete) {
                                $this->entityManager->remove($cartInsuranceToDelete);
                            }

                            $this->entityManager->flush();
                        }
                    } else {
                        $availableInsuranceSolutions = $this->availableSolutions->getAvailableInsuranceSolutions($idProduct, $idProductAttribute);
                        $invalidData = true;

                        if (!empty($availableInsuranceSolutions)) {
                            $availableSolution = reset($availableInsuranceSolutions);

                            if (!empty($availableSolution['insurances'])) {
                                foreach ($availableSolution['insurances'] as $solutionInsurance) {
                                    if ($solutionInsurance['id'] == $idInsurance && $solutionInsurance['itemId'] == $idItem) {
                                        $invalidData = false;
                                    }
                                }
                            }
                        }

                        if ($invalidData) {
                            continue;
                        }

                        $cartInsuranceToUpdate = $this->cartInsuranceRepository->findOneBy([
                            'idCart' => (int) $cart->id,
                            'idProduct' => $idProduct,
                            'idProductAttribute' => $idProductAttribute,
                        ]);

                        if (empty($cartInsuranceToUpdate)) {
                            $cartInsurance = new ScalexpertCartInsurance();
                        } else {
                            $cartInsurance = $cartInsuranceToUpdate;
                        }

                        $cartInsurance->setIdProduct((int) $idProduct);
                        $cartInsurance->setIdProductAttribute(!empty($idProductAttribute) ? (int) $idProductAttribute : $idProductAttribute);
                        $cartInsurance->setQuotations(null);
                        $cartInsurance->setIdCart((int) $cart->id);
                        $cartInsurance->setIdItem($idItem);
                        $cartInsurance->setIdInsurance($idInsurance);
                        $cartInsurance->setSolutionCode($solutionCode);

                        $idInsuranceProduct = $this->createOrGetInsuranceProduct($cartInsurance);
                        $cartInsurance->setIdInsuranceProduct($idInsuranceProduct);

                        $this->entityManager->persist($cartInsurance);
                        $this->entityManager->flush();
                    }
                }
            }
        }
    }

    public function handleCartQty($cart)
    {
        try {
            $cartProducts = $cart->getProducts();
        } catch (\Exception $exception) {
            $cartProducts = [];
        }

        $addAction = \Tools::getValue('add');
        $qty = (int) \Tools::getValue('qty', 1);
        $idProduct = \Tools::getValue('id_product', 0);
        $cartInsurances = $this->cartInsuranceRepository->findBy(['idCart' => (int) $cart->id]);

        if (!empty($cartProducts)) {
            foreach ($cartProducts as $cartProduct) {
                foreach ($cartInsurances as $cartInsurance) {
                    $cartProductAttribute = empty($cartProduct['id_product_attribute']) ? null : $cartProduct['id_product_attribute'];

                    if ($cartInsurance->getIdProduct() == $cartProduct['id_product'] && $cartInsurance->getIdProductAttribute() == $cartProductAttribute) {
                        $insuranceProductQtyInCart = $cart->containsProduct($cartInsurance->getIdInsuranceProduct());

                        if (empty($insuranceProductQtyInCart['quantity'])) {
                            $cart->updateQty(
                                $cartProduct['cart_quantity'],
                                $cartInsurance->getIdInsuranceProduct(),
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

                            $cart->updateQty(
                                $qtyDiff,
                                $cartInsurance->getIdInsuranceProduct(),
                                null,
                                false,
                                $action
                            );
                        }
                    }
                }

                if ($cartProduct['id_category_default'] == $this->configuration->get(self::CONFIGURATION_INSURANCE_PRODUCTS_CATEGORY)) {
                    $linkedLine = $this->cartInsuranceRepository->findBy(
                        [
                            'idCart' => (int) $cart->id,
                            'idInsuranceProduct' => $cartProduct['id_product'],
                        ]
                    );

                    if (empty($linkedLine)) {
                        $cart->deleteProduct($cartProduct['id_product']);
                    }
                }
            }
        } elseif (!empty($addAction) && !empty($idProduct) && !empty($qty)) {
            foreach ($cartInsurances as $cartInsurance) {
                if ($cartInsurance->getIdProduct() == $idProduct) {
                    $cart->updateQty(
                        $qty,
                        $cartInsurance->getIdInsuranceProduct(),
                        null,
                        false,
                        'up'
                    );
                }
            }
        }
    }

    public function handleQuotationsInCart($cart)
    {
        $quotationProducts = $this->cartInsuranceRepository->findBy(['idCart' => (int) $cart->id]);
        $addAction = \Tools::getValue('add');
        $qty = (int) \Tools::getValue('qty', 1);
        $idProduct = \Tools::getValue('id_product', 0);

        if (!empty($quotationProducts)) {
            foreach ($quotationProducts as $quotationProduct) {
                $productInCart = $cart->containsProduct($quotationProduct->getIdProduct(), $quotationProduct->getIdProductAttribute());

                if (empty($productInCart['quantity']) && !empty($addAction) && $idProduct == $quotationProduct->getIdProduct()) {
                    $productInCart['quantity'] = $qty;
                }

                if (empty($productInCart['quantity'])) {
                    $this->entityManager->remove($quotationProduct);
                    $this->entityManager->flush();

                    $cart->deleteProduct($quotationProduct->getIdProduct(), $quotationProduct->getIdProductAttribute());
                } else if (empty($quotationProduct->getQuotations())) {
                    $this->adjustQuotationsForCartInsurance($productInCart['quantity'], 'up', $quotationProduct);
                } else {
                    $quotationsCount = count($quotationProduct->getQuotations());

                    if ($productInCart['quantity'] > $quotationsCount) {
                        $this->adjustQuotationsForCartInsurance($productInCart['quantity'] - $quotationsCount, 'up', $quotationProduct);
                    } elseif ($productInCart['quantity'] < $quotationsCount) {
                        $this->adjustQuotationsForCartInsurance($quotationsCount - $productInCart['quantity'], 'down', $quotationProduct);
                    }
                }
            }
        }
    }

    public function adjustQuotationsForCartInsurance($quotationsDiff, $operator, $cartInsuranceLine)
    {
        $itemPrice = \Product::getPriceStatic(
            $cartInsuranceLine->getIdProduct(),
            true,
            $cartInsuranceLine->getIdProductAttribute(),
            2
        );

        $quotations = $cartInsuranceLine->getQuotations();

        if ($operator == 'up') {
            for ($quote = 0; $quote < $quotationsDiff; $quote++) {

                $quoteData = $this->apiClient->createInsuranceQuotation(
                    $cartInsuranceLine->getSolutionCode(),
                    $cartInsuranceLine->getIdItem(),
                    $itemPrice,
                    $cartInsuranceLine->getIdInsurance()
                );

                $quotations[] = $quoteData;
            }
        } else {
            $quotations = array_slice($quotations, 0, count($quotations) - $quotationsDiff);
        }

        $cartInsuranceLine->setQuotations($quotations);
        $this->entityManager->persist($cartInsuranceLine);
        $this->entityManager->flush();
    }

    public function createOrGetInsuranceProduct($cartInsurance): ?int
    {
        $insuranceProductReference =  sprintf('%s|%s', $cartInsurance->getIdItem(), $cartInsurance->getIdInsurance());
        $insuranceCategoryId = (int) $this->configuration->get(self::CONFIGURATION_INSURANCE_PRODUCTS_CATEGORY);
        $insuranceProductId = \Product::getIdByReference($insuranceProductReference);

        if (!empty($insuranceProductId)) {
            $existingInsuranceProduct = new \Product((int) $insuranceProductId);

            if (\Validate::isLoadedObject($existingInsuranceProduct)) {
                if ($existingInsuranceProduct->id_category_default != $insuranceCategoryId) {
                    $existingInsuranceProduct->id_category_default = $insuranceCategoryId;
                    $existingInsuranceProduct->addToCategories($insuranceCategoryId);
                    $existingInsuranceProduct->save();
                }

                return (int) $insuranceProductId;
            }
        }

        $productPrice = \Product::getPriceStatic($cartInsurance->getIdProduct(), true, $cartInsurance->getIdProductAttribute(), 2);

        $insurances = $this->apiClient->getInsurancesByItemId(
            $cartInsurance->getSolutionCode(),
            $productPrice,
            $cartInsurance->getIdItem()
        );

        if (!empty($insurances)) {
            foreach ($insurances as $insurance) {
                if ($insurance['id'] == $cartInsurance->getIdInsurance()) {
                    $currentInsurance = $insurance;
                }
            }
        }

        if (!empty($currentInsurance)) {
            $insuranceProduct = new \Product();
            $insuranceProduct->reference = $insuranceProductReference;
            $insuranceProduct->product_type = 'virtual';
            $insuranceProduct->is_virtual = true;
            $insuranceProduct->active = true;
            $insuranceProduct->visibility = 'none';
            $insuranceProduct->available_for_order = true;
            $insuranceProduct->delivery_out_stock = true;
            $insuranceProduct->price = \Tools::ps_round($currentInsurance['price'], 5);

            if (!empty($insuranceCategoryId)) {
                $insuranceProduct->id_category_default = $insuranceCategoryId;
                $insuranceProduct->addToCategories($insuranceCategoryId);
            }

            $languages = \Language::getLanguages();
            $insuranceProductName = [];

            $product = new \Product((int) $cartInsurance->getIdProduct());

            if (!empty($languages)) {
                foreach ($languages as $language) {
                    $insuranceProductName[$language['id_lang']] = sprintf(
                        '%s - %s',
                        $product->name[$language['id_lang']],
                        $currentInsurance['description']
                    );
                }
            }

            $insuranceProduct->name = $insuranceProductName;

            if ($insuranceProduct->save()) {
                \StockAvailable::setQuantity((int) $insuranceProduct->id, 0, 999999999);

                $productDownload = new \ProductDownload();
                $productDownload->id_product = $insuranceProduct->id;
                $productDownload->save();

                if (!empty($insuranceCategoryId)) {
                    $insuranceProduct->addToCategories([$insuranceCategoryId]);
                }

                return (int) $insuranceProduct->id;
            }
        }

        return null;
    }
}
