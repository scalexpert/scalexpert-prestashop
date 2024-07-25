<?php

namespace ScalexpertPlugin\Service;

use ScalexpertPlugin\Helper\API\Client;

class SubscriptionDeliverer
{
    private $apiClient;
    private $availableSolutionsService;


    public function __construct(
        Client $apiClient,
        AvailableSolutionsService $availableSolutionsService
    )
    {
        $this->apiClient = $apiClient;
        $this->availableSolutionsService = $availableSolutionsService;
    }

    public function deliverFinancialSubscription(
        string $financialSubscriptionId,
        string $financialSubscriptionOperator,
        string $trackingNumber,
        array $financialSubscriptions,
        $translator,
        $flashBag,
        $router
    ): void
    {
        if (!empty($financialSubscriptionId)) {
            $creditSubscriptionAvailable = false;
            $solutionCode = '';

            foreach ($financialSubscriptions as $financialSubscription) {
                // If subscription should not be available for delivery
                if (
                    $financialSubscriptionId === $financialSubscription['creditSubscriptionId']
                    && true === $financialSubscription['displayDeliveryConfirmation']
                ) {
                    $creditSubscriptionAvailable = true;
                    $solutionCode = $financialSubscription['solutionCode'];
                }
            }

            if (
                !$creditSubscriptionAvailable
                || (
                    $this->availableSolutionsService->isFrenchLongFinancingSolution($solutionCode)
                    && empty($trackingNumber)
                )
            ) {
                $flashBag->add('error',
                    $translator->trans(
                        'Financing is not accepted or tracking number is empty.',
                        []
                        , 'Modules.Scalexpertplugin.Admin'
                    )
                );
            } else {
                $responseConfirmDelivery = $this->apiClient->confirmDeliveryFinancingSubscription(
                    $financialSubscriptionId,
                    $trackingNumber,
                    $financialSubscriptionOperator,
                    $this->availableSolutionsService->isFrenchLongFinancingSolution($solutionCode)
                );

                if (
                    isset($responseConfirmDelivery['code'])
                    && 204 === (int)$responseConfirmDelivery['code']
                ) {
                    $flashBag->add('success',
                        $translator->trans(
                            'Delivery confirmation success.',
                            []
                            , 'Modules.Scalexpertplugin.Admin'
                        )
                    );
                } elseif (
                    isset($responseConfirmDelivery['code'])
                    && 409 === (int)$responseConfirmDelivery['code']
                ) {
                    $flashBag->add('error',
                        $translator->trans(
                            'The application has been already delivered.',
                            []
                            , 'Modules.Scalexpertplugin.Admin'
                        )
                    );
                } else {
                    $flashBag->add('error',
                        $translator->trans(
                            'An Error occurred during delivery confirmation process.',
                            []
                            , 'Modules.Scalexpertplugin.Admin'
                        )
                    );
                }
            }
        }

        \Tools::redirectAdmin(
            $router->generate('admin_orders_view', [
                'orderId' => (int)\Tools::getValue('id_order')
            ])
        );
    }
}
