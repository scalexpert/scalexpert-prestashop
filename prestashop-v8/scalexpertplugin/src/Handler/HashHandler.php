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

class HashHandler
{
    public function encrypt($value)
    {
        if (empty($value)) {
            return '';
        }

        $cipherTool = $this->getCipherTool();

        return $cipherTool->encrypt($value);
    }

    public function decrypt($value)
    {
        if (empty($value)) {
            return '';
        }

        $cipherTool = $this->getCipherTool();

        return $cipherTool->decrypt($value);
    }

    private function getCipherTool()
    {
        return new \PhpEncryption(_NEW_COOKIE_KEY_);
    }
}