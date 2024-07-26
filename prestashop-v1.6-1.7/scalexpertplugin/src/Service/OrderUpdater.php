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

use Configuration;
use PrestaShopModuleException;
use Validate;

class OrderUpdater
{
    /**
     * @param $order
     * @param string $status
     * @throws PrestaShopModuleException
     */
    public static function updateOrderStateBasedOnFinancingStatus($order, $status = '')
    {
        $orderStateMapping = json_decode(Configuration::get('SCALEXPERT_ORDER_STATE_MAPPING'), true);
        if (
            empty($status)
            || empty($orderStateMapping)
            || !Validate::isLoadedObject($order)
        ) {
            throw new PrestaShopModuleException('Invalid parameter data.');
        }

        $newOrderStateId = (!empty($orderStateMapping[$status])) ? $orderStateMapping[$status] : null;
        if (
            $newOrderStateId
            && (int)$newOrderStateId !== (int)$order->current_state
        ) {
            $order->setCurrentState($newOrderStateId);
        }
    }
}
