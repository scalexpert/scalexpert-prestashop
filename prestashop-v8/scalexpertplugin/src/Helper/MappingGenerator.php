<?php

namespace ScalexpertPlugin\Helper;

use ScalexpertPlugin;
use ScalexpertPlugin\Form\Configuration\MappingConfigurationFormDataConfiguration;

class MappingGenerator
{
    public static function generateDefaultMapping(): bool
    {
        $mapping = [];
        foreach (MappingConfigurationFormDataConfiguration::FINANCING_STATES as $state) {
            if (in_array($state, MappingConfigurationFormDataConfiguration::EXCLUDED_FINANCING_STATES)) {
                continue;
            }

            $idOrderState = 0;
            if (in_array(
                $state,
                [
                    'INITIALIZED',
                    'PRE_ACCEPTED'
                ]
            )) {
                $idOrderState = \Configuration::get(ScalexpertPlugin::CONFIGURATION_ORDER_STATE_FINANCING);
            }
            if ('ACCEPTED' === $state) {
                $idOrderState = \Configuration::get('PS_OS_PAYMENT');
            }
            if ('REJECTED' === $state) {
                $idOrderState = \Configuration::get('PS_OS_ERROR');
            }
            if (in_array(
                $state,
                [
                    'ABORTED',
                    'CANCELLED'
                ]
            )) {
                $idOrderState = \Configuration::get('PS_OS_CANCELED');
            }

            $mapping[$state] = $idOrderState;
        }

        return \Configuration::updateValue(
            MappingConfigurationFormDataConfiguration::CONFIGURATION_MAPPING,
            json_encode($mapping)
        );
    }
}
