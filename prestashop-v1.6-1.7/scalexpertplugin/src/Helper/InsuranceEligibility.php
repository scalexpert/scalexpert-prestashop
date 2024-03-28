<?php
/**
 * Copyright © Scalexpert.
 * This file is part of Scalexpert plugin for PrestaShop. See COPYING.md for license details.
 *
 * @author    Scalexpert (https://scalexpert.societegenerale.com/)
 * @copyright Scalexpert
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */


namespace ScalexpertPlugin\Helper;

use Configuration;

class InsuranceEligibility extends Eligibility
{
    public static function getConfigSolutions()
    {
        return json_decode(Configuration::get('SCALEXPERT_INSURANCE_SOLUTIONS'), true);
    }
}
