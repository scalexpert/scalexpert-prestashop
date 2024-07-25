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
        Client                 $apiClient,
        ConfigurationInterface $configuration,
        LegacyContext          $context
    )
    {
        $this->apiClient = $apiClient;
        $this->configuration = $configuration;
        $this->context = $context;
        $this->legacyContext = $context->getContext();
    }

    public function getContextBuyerBillingCountry(): string
    {
        return $this->legacyContext->language->iso_code;
    }

    public function getAvailableFinancialSolutions(
        $productID = null,
        $productIDAttribute = null
    ): array
    {
        $productPrice = $this->getProductPrice($productID, $productIDAttribute);
        $buyerBillingCountry = $this->getContextBuyerBillingCountry();
        $financialSolutions = $this->apiClient->getFinancialSolutions($productPrice, $buyerBillingCountry);

        return $this->getAvailableSolutions($financialSolutions, $productID);
    }

    public function getAvailableInsuranceSolutions(
        string $context,
               $productID = null,
               $productIDAttribute = null
    ): array
    {
        $buyerBillingCountry = $this->getContextBuyerBillingCountry();
        $allInsuranceSolutions = $this->apiClient->getInsuranceSolutionsByBuyerBillingCountry($buyerBillingCountry);

        if (empty($allInsuranceSolutions)) {
            return [];
        }

        // Get active configuration
        $activeConfiguration = json_decode(
            $this->configuration->get(InsuranceConfigurationFormDataConfiguration::CONFIGURATION_INSURANCE, '{}'),
            true
        );

        // Get config configuration
        $designConfiguration = json_decode(
            $this->configuration->get(DesignCustomizeFormDataConfiguration::CONFIGURATION_DESIGN, '{}'),
            true
        );

        if (isset($this->legacyContext->cart)) {
            $cartProducts = $this->legacyContext->cart->getProducts();
        }

        $insuranceSolutions = [];
        foreach ($allInsuranceSolutions as $solutionCode => $insuranceSolution) {
            // Case solution is not active in configuration
            if (empty($activeConfiguration[$solutionCode])) {
                continue;
            }

            if (!isset($designConfiguration[$solutionCode])) {
                continue;
            }

            if ('cart' === $context && !empty($cartProducts)) {
                foreach ($cartProducts as $cartProduct) {
                    $insuranceSolutionData = $this->getInsuranceData(
                        (int)$cartProduct['id_product'],
                        $cartProduct['id_product_attribute'],
                        $solutionCode,
                        $insuranceSolution,
                        $designConfiguration[$solutionCode]
                    );
                    if (empty($insuranceSolutionData)) {
                        continue;
                    }

                    $insuranceSolutionData['designConfiguration'] = $designConfiguration[$solutionCode];
                    $insuranceSolutionData['relatedProduct'] = $cartProduct;
                    $insuranceSolutions[] = $insuranceSolutionData;
                }
            } else {
                $insuranceSolutionData = $this->getInsuranceData(
                    (int)$productID,
                    $productIDAttribute,
                    $solutionCode,
                    $insuranceSolution,
                    $designConfiguration[$solutionCode]
                );
                if (empty($insuranceSolutionData)) {
                    continue;
                }

                $insuranceSolutionData['designConfiguration'] = $designConfiguration[$solutionCode];
                $insuranceSolutionData['productID'] = $productID;
                $insuranceSolutions[] = $insuranceSolutionData;
            }
        }

        return $insuranceSolutions;
    }

    protected function getInsuranceData(
        int    $idProduct,
               $idProductAttribute,
        string $solutionCode,
        array  $insuranceSolution,
        array  $designConfigurationForSolutionCode
    ): array
    {
        if (
            $this->isProductExcluded($idProduct, $designConfigurationForSolutionCode)
        ) {
            return [];
        }

        $insuranceSolutionData = $insuranceSolution;
        $itemId = $this->apiClient->createItem($solutionCode, $idProduct);

        if (!empty($itemId)) {
            $insuranceSolutionData['itemId'] = $itemId;

            $productPrice = \Product::getPriceStatic(
                $idProduct,
                true,
                $idProductAttribute,
                2
            );

            $insurances = $this->apiClient->getInsurancesByItemId($solutionCode, $productPrice, $itemId);

            if (!empty($insurances)) {
                $insuranceSolutionData['insurances'] = $insurances;
            }
        }

        return empty($insuranceSolutionData['insurances']) ? [] : $insuranceSolutionData;
    }

    public function getSimulationForAvailableFinancialSolutions(
        $productID = null,
        $productIDAttribute = null
    ): array
    {
        $productPrice = $this->getProductPrice($productID, $productIDAttribute);
        $buyerBillingCountry = $this->getContextBuyerBillingCountry();
        $financialSolutions = $this->apiClient->getFinancialSolutions($productPrice, $buyerBillingCountry);

        // Get config configuration
        $designConfiguration = json_decode(
            $this->configuration->get(DesignCustomizeFormDataConfiguration::CONFIGURATION_DESIGN, '{}'),
            true
        );

        $simulateResponse = $this->apiClient->simulateFinancing(
            $productPrice,
            $buyerBillingCountry,
            $this->getAvailableSolutions($financialSolutions, $productID, true)
        );

        if (
            empty($simulateResponse)
            || empty($simulateResponse['solutionSimulations'])
        ) {
            return $simulateResponse;
        }

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
            $solutionSimulations[$k]['designConfiguration']['custom'] = $designConfiguration[$solutionCode] ?? [];
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

        return $simulateResponse;
    }


    public function isFrenchLongFinancingSolution(string $solutionCode): bool
    {
        return in_array($solutionCode, [
            'SCFRLT-TXPS',
            'SCFRLT-TXTS',
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
            || $this->isDeutschLongFinancingSolution($solutionCode);
    }

    public function displayDeliveryConfirmation(
        string $solutionCode,
        bool $isDelivered
    ): bool
    {
        return !$isDelivered
            && (
                $this->isFrenchLongFinancingSolution($solutionCode)
                || $this->isDeutschLongFinancingSolution($solutionCode)
            );
    }

    public function hasFeesFinancingSolution(string $solutionCode): bool
    {
        return in_array($solutionCode, [
            'SCFRSP-3XPS',
            'SCFRSP-4XPS',
            'SCFRLT-TXPS',
        ], true);
    }

    public function getOperators(
        $solutionCode,
        $translator
    ): array
    {
        $isDeutschSolution = $this->isDeutschLongFinancingSolution($solutionCode);
        return $isDeutschSolution ? [] : [
            'UPS' => $translator->trans('UPS', [], 'Modules.Scalexpertplugin.Admin'),
            'DHL' => $translator->trans('DHL', [], 'Modules.Scalexpertplugin.Admin'),
            'CHRONOPOST' => $translator->trans('CHRONOPOST', [], 'Modules.Scalexpertplugin.Admin'),
            'LA_POSTE' => $translator->trans('LA_POSTE', [], 'Modules.Scalexpertplugin.Admin'),
            'DPD' => $translator->trans('DPD', [], 'Modules.Scalexpertplugin.Admin'),
            'RELAIS_COLIS' => $translator->trans('RELAIS_COLIS', [], 'Modules.Scalexpertplugin.Admin'),
            'MONDIAL_RELAY' => $translator->trans('MONDIAL_RELAY', [], 'Modules.Scalexpertplugin.Admin'),
            'FEDEX' => $translator->trans('FEDEX', [], 'Modules.Scalexpertplugin.Admin'),
            'GLS' => $translator->trans('GLS', [], 'Modules.Scalexpertplugin.Admin'),
            'UNKNOWN' => $translator->trans('UNKNOWN', [], 'Modules.Scalexpertplugin.Admin'),
        ];
    }

    protected function getProductPrice(
        $productID = null,
        $productIDAttribute = null
    ): float
    {
        if (empty($productID)) {
            if (isset($this->legacyContext->cart) && \Validate::isLoadedObject($this->legacyContext->cart)) {
                $cartPrice = $this->legacyContext->cart->getOrderTotal();
            }

            $productPrice = !empty($cartPrice) ? $cartPrice : 0;
        } else {
            $productPrice = \Product::getPriceStatic($productID, true, $productIDAttribute, 2);
        }

        return (float)$productPrice;
    }

    protected function getAvailableSolutions(
        array $financialSolutions,
              $productID = null,
        bool  $onlyCode = false
    ): array
    {
        if (empty($financialSolutions)) {
            return [];
        }

        // Get active configuration
        $activeConfiguration = json_decode(
            $this->configuration->get(FinancingConfigurationFormDataConfiguration::CONFIGURATION_FINANCING, '{}'),
            true
        );

        // Get config configuration
        $designConfiguration = json_decode(
            $this->configuration->get(DesignCustomizeFormDataConfiguration::CONFIGURATION_DESIGN, '{}'),
            true
        );

        if (isset($this->legacyContext->cart)) {
            $cartProducts = $this->legacyContext->cart->getProducts();
        }

        $availableSolutions = [];
        foreach ($financialSolutions as $solutionCode => $financialSolution) {
            // Case solution is not active in configuration
            if (
                empty($activeConfiguration[$solutionCode])
                || !isset($designConfiguration[$solutionCode])
            ) {
                continue;
            }

            // On product page, check productDisplay is enabled
            if (
                null !== $productID
                && !$designConfiguration[$solutionCode]['productDisplay']
            ) {
                continue;
            }

            // On product page, check that product doesn't belong to excluded products or excluded categories
            if (
                null !== $productID
                && $this->isProductExcluded(
                    (int)$productID,
                    $designConfiguration[$solutionCode]
                )
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

                    // On cart page, check that products don't belong to excluded products or excluded categories
                    if (
                        $this->isProductExcluded((int)$cartProduct['id_product'], $designConfiguration[$solutionCode])
                    ) {
                        continue 2;
                    }
                }
            }

            if ($onlyCode) {
                $availableSolutions[] = $solutionCode;
            } else {
                $financialSolutionData = $financialSolution;
                $financialSolutionData['designConfiguration'] = $designConfiguration[$solutionCode];
                $availableSolutions[] = $financialSolutionData;
            }
        }

        return $availableSolutions;
    }

    protected function isProductExcluded(
        int   $productId,
        array $exclusions
    ): bool
    {
        $productCategories = \Product::getProductCategories($productId);

        // Check category compatibility with cart products
        if (
            !empty($productCategories)
            && !empty($exclusions['excludedCategories'])
        ) {
            foreach ($exclusions['excludedCategories'] as $categoryID) {
                if (in_array($categoryID, $productCategories)) {
                    return true;
                }
            }
        }

        return !empty($exclusions['excludedProducts']['data'])
            && in_array(
                $productId,
                $exclusions['excludedProducts']['data']
            );
    }
}
