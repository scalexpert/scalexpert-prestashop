<?php

namespace ScalexpertPlugin\Service;

use ScalexpertPlugin\Helper\API\Client;

class SubscriptionCanceler
{
    private $apiClient;


    public function __construct(
        Client $apiClient
    )
    {
        $this->apiClient = $apiClient;
    }

    public function cancelFinancialSubscription(
        string $financialSubscriptionId,
        float $financialSubscriptionAmount,
        $translator,
        $flashBag,
        $router
    ): void
    {
        if (
            !empty($financialSubscriptionId)
            && !empty($financialSubscriptionAmount)
        ) {
            $responseCancel = $this->apiClient->cancelFinancialSubscription(
                $financialSubscriptionId,
                $financialSubscriptionAmount
            );

            if (
                isset($responseCancel['status'])
                && 'ACCEPTED' === $responseCancel['status']
            ) {
                $flashBag->add('success',
                    $translator->trans(
                        'Cancellation success.',
                        []
                        , 'Modules.Scalexpertplugin.Admin'
                    )
                );
            } else {
                $flashBag->add('error',
                    $translator->trans(
                        'An Error occurred during cancellation process.',
                        []
                        , 'Modules.Scalexpertplugin.Admin'
                    )
                );
            }
        }

        \Tools::redirectAdmin(
            $router->generate('admin_orders_view', [
                'orderId' => (int)\Tools::getValue('id_order')
            ])
        );
    }
}
