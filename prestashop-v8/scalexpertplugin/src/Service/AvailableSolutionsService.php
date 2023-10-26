<?php
/**
 * Copyright © Scalexpert.
 * This file is part of Scalexpert plugin for PrestaShop.
 *
 * @author    Société Générale
 * @copyright Scalexpert
 */

declare(strict_types=1);

namespace ScalexpertPlugin\Service;

use PrestaShop\PrestaShop\Adapter\LegacyContext;
use PrestaShop\PrestaShop\Core\ConfigurationInterface;
use ScalexpertPlugin\Form\Configuration\FinancingConfigurationFormDataConfiguration;
use ScalexpertPlugin\Form\Configuration\InsuranceConfigurationFormDataConfiguration;
use ScalexpertPlugin\Form\Customize\DesignCustomizeFormDataConfiguration;
use ScalexpertPlugin\Helper\API\Client;

class AvailableSolutionsService
{
    private $apiClient;

    private $configuration;

    public function __construct(Client $apiClient, ConfigurationInterface $configuration, LegacyContext $context)
    {
        $this->apiClient = $apiClient;
        $this->configuration = $configuration;
        $this->context = $context;
        $this->legacyContext = $context->getContext();
    }

    public function getContextBuyerBillingCountry()
    {
        if (isset($this->legacyContext->cart)) {
            if (!empty($this->legacyContext->cart->id_address_invoice)) {
                $addressInvoice = new \Address((int) $this->legacyContext->cart->id_address_invoice);

                if (\Validate::isLoadedObject($addressInvoice) && !empty($addressInvoice->id_country)) {
                    $buyerBillingCountry = \Country::getIsoById($addressInvoice->id_country);
                }
            }
        }

        if (empty($buyerBillingCountry)) {
            $buyerBillingCountry = $this->legacyContext->language->iso_code;
        }

        return $buyerBillingCountry;
    }

    public function getAvailableFinancialSolutions($productID = null, $productIDAttribute = null): array
    {
        $availableSolutions = [];

        if (empty($productID)) {
            if (isset($this->legacyContext->cart) && \Validate::isLoadedObject($this->legacyContext->cart)) {
                $cartPrice = $this->legacyContext->cart->getOrderTotal();
            }

            $productPrice = !empty($cartPrice) ? $cartPrice : 0;
        } else {
            $productPrice = \Product::getPriceStatic($productID, true, $productIDAttribute, 2);
        }

        $buyerBillingCountry = $this->getContextBuyerBillingCountry();
        $financialSolutions = $this->apiClient->getFinancialSolutions($productPrice, $buyerBillingCountry);

        if (!empty($financialSolutions)) {
            // Get active configuration
            $activeConfiguration = $this->configuration->get(FinancingConfigurationFormDataConfiguration::CONFIGURATION_FINANCING);
            $activeConfiguration = !empty($activeConfiguration) ? json_decode($activeConfiguration, true) : [];

            // Get config configuration
            $designConfiguration = $this->configuration->get(DesignCustomizeFormDataConfiguration::CONFIGURATION_DESIGN);
            $designConfiguration = !empty($designConfiguration) ? json_decode($designConfiguration, true) : [];

            foreach ($financialSolutions as $solutionCode => $financialSolution) {
                // Case solution is not active in configuration
                if (empty($activeConfiguration[$solutionCode])) {
                    continue;
                }

                if (isset($designConfiguration[$solutionCode])) {
                    if (isset($this->legacyContext->cart)) {
                        $cartProducts = $this->legacyContext->cart->getProducts();
                    }

                    if (!empty($designConfiguration[$solutionCode]['excluded_categories'])) {
                        if (!empty($productID)) {
                            // Check category compatibility with current product on product page
                            $productCategories = \Product::getProductCategories($productID);

                            if (!empty($productCategories)) {
                                foreach ($designConfiguration[$solutionCode]['excluded_categories'] as $categoryID) {
                                    // Restricted category for the solution
                                    if (in_array($categoryID, $productCategories)) {
                                        continue 2;
                                    }
                                }
                            }
                        }
                    }

                    if (!empty($cartProducts)) {
                        $insuranceProductsCategory = $this->configuration->get(
                            CartInsuranceProductsService::CONFIGURATION_INSURANCE_PRODUCTS_CATEGORY
                        );

                        foreach ($cartProducts as $cartProduct) {
                            // Financial solutions are not compatible with insurance products
                            if ($cartProduct['id_category_default'] == $insuranceProductsCategory) {
                                continue 2;
                            }

                            $productCategories = \Product::getProductCategories($cartProduct['id_product']);

                            if (!empty($productCategories)) {
                                // Check category compatibility with cart products
                                if (!empty($designConfiguration[$solutionCode]['excluded_categories'])) {
                                    foreach ($designConfiguration[$solutionCode]['excluded_categories'] as $categoryID) {
                                        if (in_array($categoryID, $productCategories)) {
                                            continue 3;
                                        }
                                    }
                                }
                            }
                        }
                    }
                } else {
                    continue;
                }

                $financialSolutionData = $financialSolution;
                $financialSolutionData['designConfiguration'] = $designConfiguration[$solutionCode];

                $availableSolutions[] = $financialSolutionData;
            }
        }

        return $availableSolutions;
    }

    public function getAvailableInsuranceSolutions($productID = null, $productIDAttribute = null): array
    {
        $insuranceSolutions = [];
        $buyerBillingCountry = $this->getContextBuyerBillingCountry();
        $allInsuranceSolutions = $this->apiClient->getInsuranceSolutionsByBuyerBillingCountry($buyerBillingCountry);

        if (!empty($allInsuranceSolutions)) {
            // Get active configuration
            $activeConfiguration = $this->configuration->get(InsuranceConfigurationFormDataConfiguration::CONFIGURATION_INSURANCE);
            $activeConfiguration = !empty($activeConfiguration) ? json_decode($activeConfiguration, true) : [];

            // Get config configuration
            $designConfiguration = $this->configuration->get(DesignCustomizeFormDataConfiguration::CONFIGURATION_DESIGN);
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
                    if (isset($this->legacyContext->cart)) {
                        $cartProducts = $this->legacyContext->cart->getProducts();
                    }

                    if (!empty($cartProducts)) {
                        foreach ($cartProducts as $cartProduct) {
                            $insuranceSolutionData = $insuranceSolution;

                            if (!empty($designConfiguration[$solutionCode]['excluded_categories'])) {
                                // Check category compatibility with current product on product page
                                $productCategories = \Product::getProductCategories($cartProduct['id_product']);

                                if (!empty($productCategories)) {
                                    foreach ($designConfiguration[$solutionCode]['excluded_categories'] as $categoryID) {
                                        // Restricted category for the solution
                                        if (in_array($categoryID, $productCategories)) {
                                            continue 2;
                                        }
                                    }
                                }
                            }

                            $itemId = $this->apiClient->createItem($solutionCode, $cartProduct['id_product']);

                            if (!empty($itemId)) {
                                $insuranceSolutionData['itemId'] = $itemId;

                                $productPrice = \Product::getPriceStatic(
                                    $cartProduct['id_product'],
                                    true,
                                    $cartProduct['id_product_attribute'],
                                    2
                                );

                                $insurances = $this->apiClient->getInsurancesByItemId($solutionCode, $productPrice, $itemId);

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

                    if (!empty($designConfiguration[$solutionCode]['excluded_categories'])) {
                        // Check category compatibility with current product on product page
                        $productCategories = \Product::getProductCategories($productID);

                        if (!empty($productCategories)) {
                            foreach ($designConfiguration[$solutionCode]['excluded_categories'] as $categoryID) {
                                // Restricted category for the solution
                                if (in_array($categoryID, $productCategories)) {
                                    continue 2;
                                }
                            }
                        }
                    }

                    $itemId = $this->apiClient->createItem($solutionCode, $productID);

                    if (!empty($itemId)) {
                        $insuranceSolutionData['itemId'] = $itemId;

                        $productPrice = \Product::getPriceStatic($productID, true, $productIDAttribute, 2);
                        $insurances = $this->apiClient->getInsurancesByItemId($solutionCode, $productPrice, $itemId);

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
}