<?php

namespace ScalexpertPlugin\Helper;

class InsuranceNamer
{
    public static function getInsuranceStateName($insuranceState, $module): string
    {
        $matchingStates = [
            'ACTIVATED' =>
                $module->l('Insurance subscription activated'),
            'INITIALIZED' =>
                $module->l('Insurance subscription request in progress'),
            'SUBSCRIBED' =>
                $module->l('Insurance subscribed'),
            'REJECTED' =>
                $module->l('Insurance subscription rejected'),
            'CANCELLED' =>
                $module->l('Insurance subscription cancelled'),
            'TERMINATED' =>
                $module->l('Insurance subscription terminated'),
            'ABORTED' =>
                $module->l('Insurance subscription aborted')
        ];

        return array_key_exists($insuranceState, $matchingStates) ?
            $matchingStates[$insuranceState]
            : $module->l('A technical error occurred during process, please retry.')
        ;
    }
}
