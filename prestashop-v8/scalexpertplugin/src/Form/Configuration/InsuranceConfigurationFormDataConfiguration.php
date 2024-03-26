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

namespace ScalexpertPlugin\Form\Configuration;

use PrestaShop\PrestaShop\Core\Configuration\DataConfigurationInterface;
use PrestaShop\PrestaShop\Core\ConfigurationInterface;

final class InsuranceConfigurationFormDataConfiguration implements DataConfigurationInterface
{
    const CONFIGURATION_INSURANCE = 'SCALEXPERT_INSURANCE';

    private $configuration;

    public function __construct(ConfigurationInterface $configuration)
    {
        $this->configuration = $configuration;
    }

    public function getConfiguration(): array
    {
        $configuration = [];

        $optionsFinancing = $this->configuration->get(self::CONFIGURATION_INSURANCE);

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
            $this->configuration->set(self::CONFIGURATION_INSURANCE, $formattedConfiguration);
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
