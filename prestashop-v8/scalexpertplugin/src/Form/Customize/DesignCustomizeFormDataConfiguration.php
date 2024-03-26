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

namespace ScalexpertPlugin\Form\Customize;

use PrestaShop\PrestaShop\Core\Configuration\DataConfigurationInterface;
use PrestaShop\PrestaShop\Core\ConfigurationInterface;

final class DesignCustomizeFormDataConfiguration implements DataConfigurationInterface
{
    const CONFIGURATION_DESIGN = 'SCALEXPERT_DESIGN';
    const ID_DELIMITER = '_';

    private $configuration;

    public function __construct(ConfigurationInterface $configuration)
    {
        $this->configuration = $configuration;
    }

    public function getConfiguration(): array
    {
        $configuration = [];
        $designConfiguration = $this->configuration->get(self::CONFIGURATION_DESIGN);

        if (!empty($designConfiguration)) {
            $configuration = json_decode($designConfiguration, true);

            if (!empty($configuration)) {
                $dataConfiguration = [];

                foreach ($configuration as $solutionCode => $solutionConfiguration) {
                    foreach ($solutionConfiguration as $configurationName => $configurationValue) {
                        $dataConfiguration[sprintf('%s' . self::ID_DELIMITER . '%s', $solutionCode, $configurationName)] = $configurationValue;
                    }
                }

                $configuration = $dataConfiguration;
            }
        }

        return $configuration;
    }

    public function updateConfiguration(array $configuration): array
    {
        $errors = [];

        if ($this->validateConfiguration($configuration)) {
            $formattedConfiguration = $this->formatConfiguration($configuration);
            $this->configuration->set(self::CONFIGURATION_DESIGN, $formattedConfiguration);
        }

        return $errors;
    }

    public function validateConfiguration(array $configuration): bool
    {
        return true;
    }

    public function formatConfiguration(array $configuration): string
    {
        $formattedConfiguration = [];

        foreach ($configuration as $configurationName => $configurationValue) {
            $explodedName = explode(self::ID_DELIMITER, $configurationName);
            $formattedConfiguration[$explodedName[0]][$explodedName[1]] = $configurationValue;
        }

        return json_encode($formattedConfiguration);
    }
}
