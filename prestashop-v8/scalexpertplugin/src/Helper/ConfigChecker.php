<?php
/**
 * Copyright © Scalexpert.
 * This file is part of Scalexpert plugin for PrestaShop.
 *
 * @author    Société Générale
 * @copyright Scalexpert
 */

declare(strict_types=1);

namespace ScalexpertPlugin\Helper;

use PrestaShop\PrestaShop\Core\ConfigurationInterface;
use ScalexpertPlugin\Form\Configuration\KeysConfigurationFormDataConfiguration;

class ConfigChecker
{
    private $configuration;

    public function __construct(ConfigurationInterface $configuration)
    {
        $this->configuration = $configuration;
    }

    public function checkExistingKeys(): bool
    {
        if ('production' == $this->configuration->get(KeysConfigurationFormDataConfiguration::SCALEXPERT_KEYS_TYPE)) {
            if (!empty($this->configuration->get(KeysConfigurationFormDataConfiguration::SCALEXPERT_KEYS_ID_PROD))
                && !empty($this->configuration->get(KeysConfigurationFormDataConfiguration::SCALEXPERT_KEYS_SECRET_PROD))
            ) {
                return true;
            }
        } else {
            if (!empty($this->configuration->get(KeysConfigurationFormDataConfiguration::SCALEXPERT_KEYS_ID_TEST))
                && !empty($this->configuration->get(KeysConfigurationFormDataConfiguration::SCALEXPERT_KEYS_SECRET_TEST))
            ) {
                return true;
            }
        }

        return false;
    }
}