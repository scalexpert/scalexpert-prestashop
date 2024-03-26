<?php
/**
 * Copyright © Scalexpert.
 * This file is part of Scalexpert plugin for PrestaShop. See COPYING.md for license details.
 *
 * @author    Scalexpert (https://scalexpert.societegenerale.com/)
 * @copyright Scalexpert
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

declare(strict_types=1);

namespace ScalexpertPlugin\Service;

use PrestaShop\PrestaShop\Adapter\LegacyContext;
use PrestaShop\PrestaShop\Core\ConfigurationInterface;
use ScalexpertPlugin\Form\Configuration\MappingConfigurationFormDataConfiguration;

class OrderUpdaterService
{

    private $configuration;

    public function __construct(
        ConfigurationInterface $configuration,
        LegacyContext $context
    )
    {
        $this->configuration = $configuration;
        $this->context = $context;
        $this->legacyContext = $context->getContext();
    }

    /**
     * @throws \PrestaShopModuleException
     */
    public function updateOrderStateBasedOnFinancingStatus($order, $status = ''): void
    {
        $orderStateMapping = json_decode(
            $this->configuration->get(MappingConfigurationFormDataConfiguration::CONFIGURATION_MAPPING),
            true
        );
        if (
            !\Validate::isLoadedObject($order)
            || empty($status)
            || empty($orderStateMapping)
        ) {
            throw new \PrestaShopModuleException('Invalid parameter data.');
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
