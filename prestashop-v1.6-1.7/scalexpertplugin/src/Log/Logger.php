<?php
/**
 * Copyright © Scalexpert.
 * This file is part of Scalexpert plugin for PrestaShop.
 *
 * @author    Société Générale
 * @copyright Scalexpert
 */

namespace ScalexpertPlugin\Log;

use Configuration;

class Logger
{
    const DEBUG = 0;
    const INFO = 1;
    const WARNING = 2;
    const ERROR = 3;

    public $logger;

    public $activeLog = 0;

    public function __construct()
    {
        $this->logger = new \FileLogger();
        $this->logger->setFilename(_PS_MODULE_DIR_.'scalexpertplugin/log/'.date('Ymd').'.log');

        $this->activeLog = (int)Configuration::get('SCALEXPERT_DEBUG_MODE');

    }

    /**
     * Log the debug on the disk
     */
    public function logDebug($message)
    {
        if ($this->activeLog) {
            $this->logger->log($message, self::DEBUG);
        }
    }

    /**
     * Log the info on the disk
     */
    public function logInfo($message)
    {
        if ($this->activeLog) {
            $this->logger->log($message, self::INFO);
        }
    }

    /**
     * Log the warning on the disk
     */
    public function logWarning($message)
    {
        if ($this->activeLog) {
            $this->logger->log($message, self::WARNING);
        }
    }

    /**
     * Log the error on the disk
     */
    public function logError($message)
    {
        if ($this->activeLog) {
            $this->logger->log($message, self::ERROR);
        }
    }
}
