<?php

function upgrade_module_1_2_6($module)
{
    return
        \Configuration::updateValue(
            $module::API_URL_PROD,
            'https://api.scalexpert.societegenerale.com/baas/prod'
        )
        && \Configuration::updateValue(
            $module::API_URL_TEST,
            'https://api.scalexpert.uatc.societegenerale.com/baas/uatc'
        )
    ;
}
