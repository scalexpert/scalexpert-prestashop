<?php

namespace ScalexpertPlugin\Helper;

class FinancingNamer
{
    public static function getFinancialStateName($state, $module, $backOfficeDisplay = false, $withENStatus = false): string
    {
        $matchingFOStates = [
            'REQUESTED' => $module->l('Financing request requested'),
            'INITIALIZED' => $module->l('Financing request in progress'),
            'PRE_ACCEPTED' => $module->l('Financing request pre-accepted'),
            'ACCEPTED' => $module->l('Financing request accepted'),
            'REJECTED' => $module->l('Financing request rejected'),
            'ABORTED' => $module->l('Financing request aborted'),
            'CANCELLED' => $module->l('Financing request cancelled')
        ];

        $matchingBOStates = [
            'INITIALIZED' =>
                $module->l('Credit subscription is initialized') . ($withENStatus ? ' (INITIALIZED)' : ''),
            'PRE_ACCEPTED' =>
                $module->l('Credit subscription is completed, awaiting a final decision from the financial institution') . ($withENStatus ? ' (PRE_ACCEPTED)' : ''),
            'ACCEPTED' =>
                $module->l('Credit subscription is accepted') . ($withENStatus ? ' (ACCEPTED)' : ''),
            'REJECTED' =>
                $module->l('Credit subscription is refused') . ($withENStatus ? ' (REJECTED)' : ''),
            'ABORTED' =>
                $module->l('Credit subscription is aborted by the customer or due to technical issue') . ($withENStatus ? ' (ABORTED)' : ''),
            'CANCELLED' =>
                $module->l('Credit subscription is cancelled') . ($withENStatus ? ' (CANCELLED)' : '')
        ];

        if ($backOfficeDisplay) {
           return array_key_exists($state, $matchingBOStates) ? $matchingBOStates[$state] : '';
        }

        return array_key_exists($state, $matchingFOStates) ?
            $matchingFOStates[$state]
            : $module->l('A technical error occurred during process, please retry.')
        ;
    }

    public static function getFinancialSubStateName($financingState, $module): string
    {
        $matchingStates = [
            'SUBSCRIPTION IN PROGRESS' =>
                $module->l('Souscription en cours par le client'),
            'SUBSCRIPTION PRE-APPROVED BY PRODUCER - WAITING FOR CONTRACT SIGNATURE' =>
                $module->l('En attente de signature client'),
            'CONTRACT SIGNED BY CUSTOMER' =>
                $module->l('Contrat signé par le client'),
            'WAITING CUSTOMER FOR ADDITIONAL INFORMATION' =>
                $module->l('Informations complémentaires demandées au client'),
            'SUBSCRIPTION APPROVED BY PRODUCER' =>
                $module->l('Souscription acceptée par l’organisme prêteur'),
            'WAITING FOR THE DELIVERY CONFIRMATION' =>
                $module->l('En attente de confirmation de livraison des produits par le marchand'),
            'AWAITING PAYMENT TO MERCHANT' =>
                $module->l('En attente du paiement de la commande par l’organisme prêteur'),
            'PAYMENT HAS BEEN ISSUED TO MERCHANT' =>
                $module->l('Paiement de la commande réalisé par l’organisme prêteur'),
            'SUBSCRIPTION REJECTED BY PRODUCER' =>
                $module->l('Refus de l’organisme prêteur'),
            'ABORTED BY CUSTOMER' =>
                $module->l('Abandon de la souscription par le client'),
            'ABORTED DUE TO TECHNICAL ISSUE' =>
                $module->l('Incident technique lors de la souscription'),
            'CANCELED BY CUSTOMER' =>
                $module->l('Annulation demandée par le client')
        ];

        return array_key_exists($financingState, $matchingStates) ? $matchingStates[$financingState] : '';
    }
}
