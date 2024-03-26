<?php
/**
 * Copyright © Scalexpert.
 * This file is part of Scalexpert plugin for PrestaShop. See COPYING.md for license details.
 *
 * @author    Scalexpert (https://scalexpert.societegenerale.com/)
 * @copyright Scalexpert
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */


namespace ScalexpertPlugin\Api;

class Entity
{
    public static $scope;
    public static $financindAmounts;
    public static $buyerBillingCountry;

    public static function getEligibleSolutions()
    {
        $params = [];
        if (!empty(static::$buyerBillingCountry)) {
            foreach (static::$buyerBillingCountry as $buyerBillingCountry) {
                if (!empty(static::$financindAmounts)) {
                    foreach (static::$financindAmounts as $financedAmount) {
                        $params[] = [
                            'financedAmount' => $financedAmount,
                            'buyerBillingCountry' => $buyerBillingCountry
                        ];
                    }
                } else {
                    $params[] = ['buyerBillingCountry' => $buyerBillingCountry];
                }
            }
        }

        $elligibleSolutions = [];
        foreach ($params as $param) {
            $apiUrl = static::$scope . '/api/v1/eligible-solutions?' . http_build_query($param);
            $result = Client::get($apiUrl);

            if (!$result['hasError']) {
                foreach ($result['data']['solutions'] as $solution) {
                    $elligibleSolutions[$solution['solutionCode']] = $solution;
                }
            }
        }

        return $elligibleSolutions;
    }
}
