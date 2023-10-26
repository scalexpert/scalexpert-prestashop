<?php
/**
 * Copyright © Scalexpert.
 * This file is part of Scalexpert plugin for PrestaShop.
 *
 * @author    Société Générale
 * @copyright Scalexpert
 */

namespace ScalexpertPlugin\Helper;

use Configuration;

class InsuranceEligibility extends Eligibility
{
    protected static function getConfigSolutions()
    {
        return json_decode(Configuration::get('SCALEXPERT_INSURANCE_SOLUTIONS'), true);
    }
}