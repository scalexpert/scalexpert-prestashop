<?php

namespace ScalexpertPlugin\Helper;

class SolutionFormatter
{
    public function formatSolution(
        array $solution,
        string $buyerBillingCountry,
        string $solutionType
    ): array
    {
        return [
            'solutionCode' => $solution['solutionCode'] ?? '',
            'visualTitle' => $solution['communicationKit']['visualTitle'] ?? '',
            'visualDescription' => $solution['communicationKit']['visualDescription'] ?? '',
            'visualInformationIcon' => $solution['communicationKit']['visualInformationIcon'] ?? '',
            'visualAdditionalInformation' => $solution['communicationKit']['visualAdditionalInformation'] ?? '',
            'visualLegalText' => $solution['communicationKit']['visualLegalText'] ?? '',
            'visualTableImage' => $solution['communicationKit']['visualTableImage'] ?? '',
            'visualLogo' => $solution['communicationKit']['visualLogo'] ?? '',
            'visualInformationNoticeURL' => $solution['communicationKit']['visualInformationNoticeURL'] ?? '',
            'visualProductTermsURL' => $solution['communicationKit']['visualProductTermsURL'] ?? '',
            'buyerBillingCountry' => $buyerBillingCountry,
            'countryFlag' => sprintf('/img/flags/%s.jpg', strtolower($buyerBillingCountry)),
            'type' => $solutionType,
        ];
    }
}
