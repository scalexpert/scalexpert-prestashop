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

final class MappingConfigurationFormDataConfiguration implements DataConfigurationInterface
{
    const CONFIGURATION_MAPPING = 'SCALEXPERT_ORDER_STATE_MAPPING';
    const FINANCING_STATES = [
        'INITIALIZED',
        'REQUESTED',
        'PRE_ACCEPTED',
        'ACCEPTED',
        'REJECTED',
        'ABORTED',
        'CANCELLED'
    ];
    const FINAL_FINANCING_STATES = [
        'ACCEPTED',
        'REJECTED',
        'ABORTED',
        'CANCELLED'
    ];
    const EXCLUDED_FINANCING_STATES = [
        'REQUESTED',
    ];

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
        $configuration = [];

        $mapping = $this->configuration->get(self::CONFIGURATION_MAPPING);

        if (!empty($mapping)) {
            $configuration = json_decode($mapping, true);
        }

        return $configuration;
    }

    public function updateConfiguration(array $configuration): array
    {
        $errors = [];

        if ($this->validateConfiguration($configuration)) {
            $formattedConfiguration = $this->formatConfiguration($configuration);
            $this->configuration->set(self::CONFIGURATION_MAPPING, $formattedConfiguration);
        }

        return $errors;
    }

    public function validateConfiguration(array $configuration): bool
    {
        return true;
    }

    public function formatConfiguration(array $configuration): string
    {
        return json_encode($configuration);
    }
}
