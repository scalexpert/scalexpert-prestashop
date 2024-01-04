<?php
/**
 * Copyright © Scalexpert.
 * This file is part of Scalexpert plugin for PrestaShop.
 *
 * @author    Société Générale
 * @copyright Scalexpert
 */

declare(strict_types=1);

namespace ScalexpertPlugin\Service;

use PrestaShop\PrestaShop\Adapter\LegacyContext;
use PrestaShop\PrestaShop\Core\ConfigurationInterface;
use ScalexpertPlugin\Form\Configuration\FinancingConfigurationFormDataConfiguration;
use ScalexpertPlugin\Form\Configuration\InsuranceConfigurationFormDataConfiguration;
use ScalexpertPlugin\Form\Configuration\MappingConfigurationFormDataConfiguration;
use ScalexpertPlugin\Form\Customize\DesignCustomizeFormDataConfiguration;
use ScalexpertPlugin\Helper\API\Client;

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

    public function updateOrderStateBasedOnFinancingStatus($order, $status = '')
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
            throw new \Exception('Invalid parameter data.');
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
