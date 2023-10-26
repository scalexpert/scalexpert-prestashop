<?php
/**
 * Copyright © Scalexpert.
 * This file is part of Scalexpert plugin for PrestaShop.
 *
 * @author    Société Générale
 * @copyright Scalexpert
 */

declare(strict_types=1);

namespace ScalexpertPlugin\Form\Configuration;

use PrestaShop\PrestaShop\Core\Configuration\DataConfigurationInterface;
use PrestaShop\PrestaShop\Core\ConfigurationInterface;

final class RegroupPaymentsConfigurationFormDataConfiguration implements DataConfigurationInterface
{
    const CONFIGURATION_REGROUP_PAYMENTS = 'SCALEXPERT_REGROUP_PAYMENTS';

    /**
     * @var ConfigurationInterface
     */
    private $configuration;

    public function __construct(ConfigurationInterface $configuration)
    {
        $this->configuration = $configuration;
    }

    public function getConfiguration(): array
    {
        $regroupPayments = $this->configuration->get(self::CONFIGURATION_REGROUP_PAYMENTS);

        return [self::CONFIGURATION_REGROUP_PAYMENTS => $regroupPayments ?? false];
    }

    public function updateConfiguration(array $configuration): array
    {
        $errors = [];

        if ($this->validateConfiguration($configuration)) {
            $this->configuration->set(self::CONFIGURATION_REGROUP_PAYMENTS, $configuration[self::CONFIGURATION_REGROUP_PAYMENTS]);
        }

        return $errors;
    }

    public function validateConfiguration(array $configuration): bool
    {
        return true;
    }
}