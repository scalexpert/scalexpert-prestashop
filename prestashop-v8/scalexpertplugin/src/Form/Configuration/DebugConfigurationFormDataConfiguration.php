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

final class DebugConfigurationFormDataConfiguration implements DataConfigurationInterface
{
    const CONFIGURATION_DEBUG = 'SCALEXPERT_DEBUG';

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
        $debugActive = $this->configuration->get(self::CONFIGURATION_DEBUG);

        return [self::CONFIGURATION_DEBUG => $debugActive ?? false];
    }

    public function updateConfiguration(array $configuration): array
    {
        $errors = [];

        if ($this->validateConfiguration($configuration)) {
            $this->configuration->set(self::CONFIGURATION_DEBUG, $configuration[self::CONFIGURATION_DEBUG]);
        }

        return $errors;
    }

    public function validateConfiguration(array $configuration): bool
    {
        if (!isset($configuration[self::CONFIGURATION_DEBUG])) {
            return false;
        }

        return true;
    }
}
