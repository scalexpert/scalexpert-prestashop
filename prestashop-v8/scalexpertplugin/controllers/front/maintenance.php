<?php

use ScalexpertPlugin\Service\UpdateOrdersStatesService;

/**
 * Copyright © Scalexpert.
 * This file is part of Scalexpert plugin for PrestaShop. See COPYING.md for license details.
 *
 * @author    Scalexpert (https://scalexpert.societegenerale.com/)
 * @copyright Scalexpert
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

class ScalexpertpluginMaintenanceModuleFrontController extends ModuleFrontController
{
    public function postProcess()
    {
        /* @var UpdateOrdersStatesService $updateOrdersStatesService */
        $updateOrdersStatesService = $this->get('scalexpert.service.update_orders_states');
        $updateOrdersStatesService->updateOrdersStates();
        $this->ajaxRender('Cron OK');
    }
}
