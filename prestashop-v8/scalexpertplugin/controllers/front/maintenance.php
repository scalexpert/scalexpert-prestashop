<?php
/**
 * Copyright © Scalexpert.
 * This file is part of Scalexpert plugin for PrestaShop.
 *
 * @author    Société Générale
 * @copyright Scalexpert
 */

class ScalexpertpluginMaintenanceModuleFrontController extends ModuleFrontController
{
    public function postProcess()
    {
        $updateOrdersStatesService = $this->get('scalexpert.service.update_orders_states');
        $updateOrdersStatesService->updateOrdersStates();
        die('Cron OK');
    }
}