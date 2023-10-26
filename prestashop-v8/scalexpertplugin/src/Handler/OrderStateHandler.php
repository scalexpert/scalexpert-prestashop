<?php
/**
 * Copyright © Scalexpert.
 * This file is part of Scalexpert plugin for PrestaShop.
 *
 * @author    Société Générale
 * @copyright Scalexpert
 */

declare(strict_types=1);

namespace ScalexpertPlugin\Handler;

class OrderStateHandler
{
    public function getIdOrderStateByApiStatus($status)
    {
        $status = trim(strtolower($status));

        $orderStates = [
            'initialized' => \Configuration::get('SCALEXPERT_ORDER_STATE_INITIALIZED'),
            'requested' => \Configuration::get('SCALEXPERT_ORDER_STATE_REQUESTED'),
            'pre_accepted' => \Configuration::get('SCALEXPERT_ORDER_STATE_PRE_ACCEPTED'),
            'accepted' => \Configuration::get('SCALEXPERT_ORDER_STATE_ACCEPTED'),
            'rejected' => \Configuration::get('SCALEXPERT_ORDER_STATE_REJECTED'),
            'aborted' => \Configuration::get('SCALEXPERT_ORDER_STATE_ABORTED'),
            'cancelled' => \Configuration::get('SCALEXPERT_ORDER_STATE_CANCELLED'),
        ];

        if (isset($orderStates[$status])) {
            return $orderStates[$status];
        }

        return null;
    }
}