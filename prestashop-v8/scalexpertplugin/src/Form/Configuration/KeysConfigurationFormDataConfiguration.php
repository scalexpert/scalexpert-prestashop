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
use ScalexpertPlugin\Handler\HashHandler;

/**
 * Configuration is used to save data to configuration table and retrieve from it.
 */
final class KeysConfigurationFormDataConfiguration implements DataConfigurationInterface
{
    public const SCALEXPERT_KEYS_TYPE = 'SCALEXPERT_KEYS_TYPE';
    public const SCALEXPERT_KEYS_ID_TEST = 'SCALEXPERT_KEYS_ID_TEST';
    public const SCALEXPERT_KEYS_SECRET_TEST = 'SCALEXPERT_KEYS_SECRET_TEST';
    public const SCALEXPERT_KEYS_ID_PROD = 'SCALEXPERT_KEYS_ID_PROD';
    public const SCALEXPERT_KEYS_SECRET_PROD = 'SCALEXPERT_KEYS_SECRET_PROD';

    private $configuration;

    private $hashHandler;

    public function __construct(ConfigurationInterface $configuration, HashHandler $hashHandler)
    {
        $this->configuration = $configuration;
        $this->hashHandler = $hashHandler;
    }

    public function getConfiguration(): array
    {
        $return = [];

        $return['scalexpert_keys_type'] = $this->configuration->get(static::SCALEXPERT_KEYS_TYPE);

        $return['scalexpert_keys_id_test'] = $this->configuration->get(static::SCALEXPERT_KEYS_ID_TEST);
        $return['scalexpert_keys_secret_test'] = $this->hashHandler->decrypt(
            $this->configuration->get(static::SCALEXPERT_KEYS_SECRET_TEST)
        );

        $return['scalexpert_keys_id_prod'] = $this->configuration->get(static::SCALEXPERT_KEYS_ID_PROD);
        $return['scalexpert_keys_secret_prod'] = $this->hashHandler->decrypt(
            $this->configuration->get(static::SCALEXPERT_KEYS_SECRET_PROD)
        );

        return $return;
    }

    public function updateConfiguration(array $configuration): array
    {
        $errors = [];

        if ($this->validateConfiguration($configuration)) {

            $this->configuration->set(static::SCALEXPERT_KEYS_TYPE, $configuration['scalexpert_keys_type']);

            $this->configuration->set(static::SCALEXPERT_KEYS_ID_TEST, $configuration['scalexpert_keys_id_test']);
            if (!empty($configuration['scalexpert_keys_secret_test'])) {
                $this->configuration->set(static::SCALEXPERT_KEYS_SECRET_TEST,
                    $this->hashHandler->encrypt($configuration['scalexpert_keys_secret_test'])
                );
            }

            $this->configuration->set(static::SCALEXPERT_KEYS_ID_PROD, $configuration['scalexpert_keys_id_prod']);
            if (!empty($configuration['scalexpert_keys_secret_prod'])) {
                $this->configuration->set(static::SCALEXPERT_KEYS_SECRET_PROD,
                    $this->hashHandler->encrypt($configuration['scalexpert_keys_secret_prod'])
                );
            }
        }

        /* Errors are returned here. */
        return $errors;
    }

    /**
     * Ensure the parameters passed are valid.
     *
     * @return bool Returns true if no exception are thrown
     */
    public function validateConfiguration(array $configuration): bool
    {
        return true;
    }
}
