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

use ScalexpertPlugin\Entity\CartInsurance;
use ScalexpertPlugin\Helper\API\Client;

class InsurancesSubscriptionsService
{
    private $apiClient;

    private $entityManager;

    private $cartInsuranceRepository;

    public function __construct(Client $apiClient, $entityManager)
    {
        $this->apiClient = $apiClient;
        $this->entityManager = $entityManager;
        $this->cartInsuranceRepository = $this->entityManager->getRepository(CartInsurance::class);
    }

    public function createOrderInsurancesSubscriptions($hookParams)
    {
        $order = new \Order($hookParams['id_order']);

        if (!\Validate::isLoadedObject($order)) {
            return;
        }

        if (!\Validate::isLoadedObject($hookParams['newOrderStatus'])) {
            return;
        }

        if ($hookParams['newOrderStatus']->paid) {
            $cartInsurancesProducts = $this->cartInsuranceRepository->findBy(['idCart' => $order->id_cart]);
            $cart = new \Cart($order->id_cart);
            $customer = new \Customer($order->id_customer);

            if (!\Validate::isLoadedObject($cart) || !\Validate::isLoadedObject($customer)) {
                return;
            }

            if (!empty($cartInsurancesProducts)) {
                $cartInsuranceSubscriptions = [];

                foreach ($cartInsurancesProducts as $cartInsurancesProduct) {
                    if ($cartInsurancesProduct->isSubscriptionsProcessed()) {
                        continue;
                    }

                    if (!empty($cartInsurancesProduct->getQuotations())) {
                        foreach ($cartInsurancesProduct->getQuotations() as $quoteData) {
                            $insuranceSubscription = $this->apiClient->createInsuranceSubscription(
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

                        $cartInsurancesProduct->setSubscriptionsProcessed(true);

                        if (!empty($cartInsuranceSubscriptions)) {
                            $cartInsurancesProduct->setSubscriptions($cartInsuranceSubscriptions);
                        }

                        $this->entityManager->persist($cartInsurancesProduct);
                        $this->entityManager->flush();
                    }
                }
            }
        }
    }
}