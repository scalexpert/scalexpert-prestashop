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

final class FinancingConfigurationFormDataConfiguration implements DataConfigurationInterface
{
    const CONFIGURATION_FINANCING = 'SCALEXPERT_FINANCING';

    private $configuration;

    public function __construct(ConfigurationInterface $configuration)
    {
        $this->configuration = $configuration;
    }

    public function getConfiguration(): array
    {
        $configuration = [];

        $optionsFinancing = $this->configuration->get(self::CONFIGURATION_FINANCING);

        if (!empty($optionsFinancing)) {
            $configuration = json_decode($optionsFinancing, true);
        }

        return $configuration;
    }

    public function updateConfiguration(array $configuration): array
    {
        $errors = [];

        if ($this->validateConfiguration($configuration)) {
            $formattedConfiguration = $this->formatConfiguration($configuration);
            $this->configuration->set(self::CONFIGURATION_FINANCING, $formattedConfiguration);
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
