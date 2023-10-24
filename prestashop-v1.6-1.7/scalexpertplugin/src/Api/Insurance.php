<?php
/**
 * Copyright © Scalexpert.
 * This file is part of Scalexpert plugin for PrestaShop.
 *
 * @author    Société Générale
 * @copyright Scalexpert
 */

namespace DATASOLUTION\Module\Scalexpert\Api;

class Insurance extends Entity
{
    public static $scope = 'insurance';
    public static $buyerBillingCountry = ['FR', 'DE'];

    public static function getEligibleSolutionsFromBuyerBillingCountry($buyerBillingCountry)
    {
        $params = [
            [
                'buyerBillingCountry' => $buyerBillingCountry
            ]
        ];

        $eligibleSolutions = [];
        foreach ($params as $param) {
            $apiUrl = static::$scope.'/api/v1/eligible-solutions?'.http_build_query($param);
            $result = Client::get(static::$scope, $apiUrl);

            if (!$result['hasError']) {
                foreach ($result['data']['solutions'] as $solution) {
                    if (empty($eligibleSolutions[$solution['solutionCode']])) {
                        $eligibleSolutions[$solution['solutionCode']] = $solution;
                    }
                }
            }
        }

        return $eligibleSolutions;
    }

    public static function createItem($solutionCode, $idProduct)
    {
        //todo : get data from product.

        $data = [
            "solutionCode" => "CIFRWE-DXCO",
            "sku" => "SMS4ETI14E",
            "merchantItemId" => "PS_1001",
            "brand" => "Bosch",
            "model" => "SMS4ETI14E",
            "title" => "BOSCH - Lave vaisselle 60 cm SMS4ETI14E",
            "description" => "L'EfficientDry c'est la performance de séchage améliorée grâce à l'ouverture de porte automatique en fin de cycle.Doté d'un moteur Eco Silence Drive, ce modèle de lave-vaisselle BOSCH SMS4ETI14E permet d'être extrêmement silencieux avec ses 42 dB. Nettoyez toute votre vaisselle de manière optimale avec le lave-vaisselle SMS4ETI14E de BOSCH, d'une capacité de 12 couverts et doté de 6 programmes de lavage. Profitez de la fonction de départ différé pour faire démarrer automatiquement votre lave-vaisselle au meilleur moment et réaliser ainsi des économies sur votre facture EDF. L’affichage du temps restant proposé sur ce lave-vaisselle Bosch vous permet de connaître à tout moment l’heure exacte de fin du cycle de lavage, ce qui vous permet d’organiser votre journée simplement.Ce lave-vaisselle est muni de la technologie AquaSensor qui détecte le degré de salissure, et la capacité variable automatique reconnait la charge de vaisselle afin de laver votre vaisselle afin selon ses besoins.Pour plus de confort, le panier supérieur est réglable sur 3 niveaux (RackMatic) et il est équipé de paniers VarioFlex. Pour votre sécurité, il est équipé de l'Aquastop 100% anti-fuite et d'une sécurité enfants par le verrouillage de la porte.",
            "characteristics" => "Programme Silence 42 dB. Moteur EcoSilence Drive. Echangeur Thermique. EfficientDry. VarioSpeed Plus. Départ différé et Affichage du temps restant. AquaSensor. Butée (stopper) qui empêche le déraillement du panier inférieur.",
            "category" => "lave-vaisselle"
        ];

        $apiUrl = static::$scope.'/api/v1/items';
        $result = Client::post(static::$scope, $apiUrl, $data);

        if ($result['hasError']) {
            return [];
        }

        return $result['data'];
    }

    public static function searchInsurances($solutionCode, $itemId, $priceItem)
    {
        $data = [
            'solutionCode' => $solutionCode,
            'itemId' => $itemId,
            'price' => $priceItem
        ];

        $apiUrl = static::$scope.'/api/v1/items/_search-insurances';
        $result = Client::post(static::$scope, $apiUrl, $data);

        if ($result['hasError']) {
            return [];
        }

        return $result['data'];
    }
}
