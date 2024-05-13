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

use PrestaShop\PrestaShop\Adapter\LegacyContext;
use PrestaShop\PrestaShop\Core\ConfigurationInterface;
use PrestaShop\PrestaShop\Core\Localization\Locale;
use ScalexpertPlugin\Form\Configuration\FinancingConfigurationFormDataConfiguration;
use ScalexpertPlugin\Form\Configuration\InsuranceConfigurationFormDataConfiguration;
use ScalexpertPlugin\Form\Customize\DesignCustomizeFormDataConfiguration;
use ScalexpertPlugin\Helper\API\Client;

class AvailableSolutionsService
{
    private $apiClient;

    private $configuration;

    public function __construct(
        Client $apiClient,
        ConfigurationInterface $configuration,
        LegacyContext $context
    )
    {
        $this->apiClient = $apiClient;
        $this->configuration = $configuration;
        $this->context = $context;
        $this->legacyContext = $context->getContext();
    }

    public function getContextBuyerBillingCountry()
    {
        if (
            isset($this->legacyContext->cart)
            && !empty($this->legacyContext->cart->id_address_invoice)
        ) {
            $addressInvoice = new \Address((int) $this->legacyContext->cart->id_address_invoice);

            if (\Validate::isLoadedObject($addressInvoice) && !empty($addressInvoice->id_country)) {
                $buyerBillingCountry = \Country::getIsoById($addressInvoice->id_country);
            }
        }

        if (empty($buyerBillingCountry)) {
            $buyerBillingCountry = $this->legacyContext->language->iso_code;
        }

        return $buyerBillingCountry;
    }

    public function getAvailableFinancialSolutions(
        $productID = null,
        $productIDAttribute = null
    ): array
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
            $activeConfiguration = $this->configuration->get(
                FinancingConfigurationFormDataConfiguration::CONFIGURATION_FINANCING
            );
            $activeConfiguration = !empty($activeConfiguration) ? json_decode($activeConfiguration, true) : [];

            // Get config configuration
            $designConfiguration = $this->configuration->get(
                DesignCustomizeFormDataConfiguration::CONFIGURATION_DESIGN
            );
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

                    if (
                        !empty($designConfiguration[$solutionCode]['excludedCategories'])
                        && !empty($productID)
                    ) {
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

                    if (
                        !empty($designConfiguration[$solutionCode]['excludedProducts']['data'])
                        && !empty($productID)
                        && in_array($productID, $designConfiguration[$solutionCode]['excludedProducts']['data'])
                    ) {
                        continue;
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

                            // Check category compatibility with cart products
                            if (
                                !empty($productCategories)
                                && !empty($designConfiguration[$solutionCode]['excludedCategories'])
                            ) {
                                foreach ($designConfiguration[$solutionCode]['excludedCategories'] as $categoryID) {
                                    if (in_array($categoryID, $productCategories)) {
                                        continue 3;
                                    }
                                }
                            }

                            if (
                                !empty($designConfiguration[$solutionCode]['excludedProducts']['data'])
                                && in_array($cartProduct['id_product'], $designConfiguration[$solutionCode]['excludedProducts']['data'])
                            ) {
                                continue 2;
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

    public function getAvailableInsuranceSolutions(
        $productID = null,
        $productIDAttribute = null
    ): array
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

                            if (
                                !empty($designConfiguration[$solutionCode]['excludedProducts']['data'])
                                && in_array($cartProduct['id_product'], $designConfiguration[$solutionCode]['excludedProducts']['data'])
                            ) {
                                continue;
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

                    // Restricted products for the solution
                    if (
                        !empty($designConfiguration[$solutionCode]['excludedProducts']['data'])
                        && in_array($productID, $designConfiguration[$solutionCode]['excludedProducts']['data'])
                    ) {
                        continue;
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

    public function getSimulationForAvailableFinancialSolutions(
        $productID = null,
        $productIDAttribute = null
    ): array
    {
        if (empty($productID)) {
            if (
                isset($this->legacyContext->cart)
                && \Validate::isLoadedObject($this->legacyContext->cart)
            ) {
                $cartPrice = $this->legacyContext->cart->getOrderTotal();
            }

            $productPrice = !empty($cartPrice) ? $cartPrice : 0;
        } else {
            $productPrice = \Product::getPriceStatic($productID, true, $productIDAttribute, 2);
        }

        $buyerBillingCountry = $this->getContextBuyerBillingCountry();
        $financialSolutions = $this->apiClient->getFinancialSolutions($productPrice, $buyerBillingCountry);
        // Get config configuration
        $designConfiguration = $this->configuration->get(
            DesignCustomizeFormDataConfiguration::CONFIGURATION_DESIGN
        );
        $designConfiguration = !empty($designConfiguration) ? json_decode($designConfiguration, true) : [];

        $availableSolutions = [];
        if (!empty($financialSolutions)) {
            // Get active configuration
            $activeConfiguration = $this->configuration->get(
                FinancingConfigurationFormDataConfiguration::CONFIGURATION_FINANCING
            );
            $activeConfiguration = !empty($activeConfiguration) ? json_decode($activeConfiguration, true) : [];

            foreach ($financialSolutions as $solutionCode => $financialSolution) {
                // Case solution is not active in configuration
                if (empty($activeConfiguration[$solutionCode])) {
                    continue;
                }

                if (isset($designConfiguration[$solutionCode])) {
                    // On product page, check productDisplay is enabled
                    if (
                        null !== $productID
                        && !$designConfiguration[$solutionCode]['productDisplay']
                    ) {
                        continue;
                    }

                    if (isset($this->legacyContext->cart)) {
                        $cartProducts = $this->legacyContext->cart->getProducts();
                    }

                    if (
                        !empty($designConfiguration[$solutionCode]['excludedCategories'])
                        && !empty($productID)
                    ) {
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

                    if (
                        !empty($designConfiguration[$solutionCode]['excludedProducts']['data'])
                        && !empty($productID)
                        && in_array($productID, $designConfiguration[$solutionCode]['excludedProducts']['data'])
                    ) {
                        continue;
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

                            // Check category compatibility with cart products
                            if (
                                !empty($productCategories)
                                && !empty($designConfiguration[$solutionCode]['excludedCategories'])
                            ) {
                                foreach ($designConfiguration[$solutionCode]['excludedCategories'] as $categoryID) {
                                    if (in_array($categoryID, $productCategories)) {
                                        continue 3;
                                    }
                                }
                            }

                            if (
                                !empty($designConfiguration[$solutionCode]['excludedProducts']['data'])
                                && in_array(
                                    $cartProduct['id_product'],
                                    $designConfiguration[$solutionCode]['excludedProducts']['data']
                                )
                            ) {
                                continue 2;
                            }
                        }
                    }
                } else {
                    continue;
                }

                $availableSolutions[] = $solutionCode;
            }
        }

        $simulateResponse = $this->apiClient->simulateFinancing(
            $productPrice,
            $buyerBillingCountry,
            $availableSolutions
        );

        if(
            !empty($simulateResponse)
            && !empty($simulateResponse['solutionSimulations'])
        ) {
            $currentLocale = $this->legacyContext->getCurrentLocale();
            $currentCurrencyCode = $this->legacyContext->currency->iso_code;

            $simulateResponse['financedAmountFormatted'] = $currentLocale->formatPrice(
                $simulateResponse['financedAmount'],
                $currentCurrencyCode
            );

            $solutionSimulations = &$simulateResponse['solutionSimulations'];
            foreach ($solutionSimulations as $k => $solutionSimulation) {
                $solutionCode = $solutionSimulation['solutionCode'];

                // Add customization data
                $solutionSimulations[$k]['designConfiguration'] = $financialSolutions[$solutionCode];
                $solutionSimulations[$k]['designConfiguration']['custom'] = $designConfiguration[$solutionCode];
                if ($productID) {
                    if (!empty($designConfiguration[$solutionCode]['productTitle'])) {
                        $solutionSimulations[$k]['designConfiguration']['visualTitle'] =
                            $designConfiguration[$solutionCode]['productTitle'];
                    }

                    if (empty($designConfiguration[$solutionCode]['productDisplayLogo'])) {
                        $solutionSimulations[$k]['designConfiguration']['visualLogo'] = null;
                    }
                } else {
                    if (!empty($designConfiguration[$solutionCode]['paymentTitle'])) {
                        $solutionSimulations[$k]['designConfiguration']['visualTitle'] =
                            $designConfiguration[$solutionCode]['paymentTitle'];
                    }

                    if (empty($designConfiguration[$solutionCode]['paymentDisplayLogo'])) {
                        $solutionSimulations[$k]['designConfiguration']['visualLogo'] = null;
                    }
                }

                // Add context data
                $solutionSimulations[$k]['isLongFinancingSolution'] = $this->isLongFinancingSolution($solutionCode);
                $solutionSimulations[$k]['hasFeesSolution'] = $this->hasFeesFinancingSolution($solutionCode);

                foreach ($solutionSimulation['simulations'] as $j => $simulation) {
                    // Refactor percentage data
                    $solutionSimulations[$k]['simulations'][$j]['effectiveAnnualPercentageRateFormatted'] = $simulation['effectiveAnnualPercentageRate'] . '%';
                    $solutionSimulations[$k]['simulations'][$j]['nominalPercentageRateFormatted'] = $simulation['nominalPercentageRate'] . '%';

                    // Refactor price data
                    $solutionSimulations[$k]['simulations'][$j]['totalCostFormatted'] = $currentLocale->formatPrice(
                        $simulation['totalCost'],
                        $currentCurrencyCode
                    );
                    $solutionSimulations[$k]['simulations'][$j]['dueTotalAmountFormatted'] = $currentLocale->formatPrice(
                        $simulation['dueTotalAmount'],
                        $currentCurrencyCode
                    );
                    $solutionSimulations[$k]['simulations'][$j]['feesAmountFormatted'] = $currentLocale->formatPrice(
                        $simulation['feesAmount'],
                        $currentCurrencyCode
                    );

                    foreach ($simulation['installments'] as $i => $installment) {
                        // Refactor price data
                        $solutionSimulations[$k]['simulations'][$j]['installments'][$i]['amountFormatted'] = $currentLocale->formatPrice(
                            $installment['amount'],
                            $currentCurrencyCode
                        );
                    }
                }
            }
        }

        return $simulateResponse;
    }


    public function isFrenchLongFinancingSolution(string $solutionCode): bool
    {
        return in_array($solutionCode, [
            'SCFRLT-TXPS',
            'SCFRLT-TXNO',
        ], true);
    }

    public function isDeutschLongFinancingSolution(string $solutionCode): bool
    {
        return in_array($solutionCode, [
            'SCDELT-DXTS',
            'SCDELT-DXCO',
        ], true);
    }

    public function isLongFinancingSolution(string $solutionCode): bool
    {
        return
            $this->isFrenchLongFinancingSolution($solutionCode)
            || $this->isDeutschLongFinancingSolution($solutionCode)
        ;
    }

    public function hasFeesFinancingSolution(string $solutionCode): bool
    {
        return in_array($solutionCode, [
            'SCFRSP-3XPS',
            'SCFRSP-4XPS',
            'SCFRLT-TXPS',
        ], true);
    }
}
