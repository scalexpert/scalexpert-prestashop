<?php
/**
 * Copyright © Scalexpert.
 * This file is part of Scalexpert plugin for PrestaShop. See COPYING.md for license details.
 *
 * @author    Scalexpert (https://scalexpert.societegenerale.com/)
 * @copyright Scalexpert
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

namespace ScalexpertPlugin\Service;

class SolutionSorter
{
    public static function sortSolutionsByPosition(&$solutions)
    {
        uasort($solutions, function ($a, $b) {
            return $a['position'] > $b['position'];
        });
    }

    public static function sortSolutionsByDuration(&$solutions)
    {
        uasort($solutions, function ($a, $b) {
            return $a['duration'] > $b['duration'];
        });
    }
}
