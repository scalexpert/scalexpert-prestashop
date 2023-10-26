<?php
/**
 * Copyright © Scalexpert.
 * This file is part of Scalexpert plugin for PrestaShop.
 *
 * @author    Société Générale
 * @copyright Scalexpert
 */

declare(strict_types=1);

namespace ScalexpertPlugin\Handler;

use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger;
use PrestaShop\PrestaShop\Core\ConfigurationInterface;
use ScalexpertPlugin\Form\Configuration\DebugConfigurationFormDataConfiguration;

class LogsHandler
{
    private $configuration;

    const LOG_FILE = 'scalexperplugin.log';

    public function __construct(ConfigurationInterface $configuration)
    {
        $this->configuration = $configuration;
    }

    public function isDebugActive(): bool
    {
        $debugConfiguration = $this->configuration->get(DebugConfigurationFormDataConfiguration::CONFIGURATION_DEBUG);

        return !empty($debugConfiguration);
    }

    public function addLog(string $message, array $data, int $level): void
    {
        if ($this->isDebugActive()) {
            $log = new Logger('main');
            $log->pushHandler(new RotatingFileHandler(_PS_MODULE_DIR_ . '/scalexpertplugin/logs/' . self::LOG_FILE, 10));
            $log->addRecord($level, $message, $data);
        }
    }
}