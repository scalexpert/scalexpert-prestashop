<?php

declare(strict_types=1);

namespace ScalexpertPlugin\Form\Customize;

use PrestaShop\PrestaShop\Core\Configuration\DataConfigurationInterface;
use PrestaShop\PrestaShop\Core\ConfigurationInterface;

final class DesignCustomizeFormDataConfiguration implements DataConfigurationInterface
{
    const CONFIGURATION_DESIGN = 'SCALEXPERT_DESIGN';

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
                        $dataConfiguration[sprintf('%s:%s', $solutionCode, $configurationName)] = $configurationValue;
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
            $explodedName = explode(':', $configurationName);
            $formattedConfiguration[$explodedName[0]][$explodedName[1]] = $configurationValue;
        }

        return json_encode($formattedConfiguration);
    }
}