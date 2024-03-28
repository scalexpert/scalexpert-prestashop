<?php
/**
 * Copyright © Scalexpert.
 * This file is part of Scalexpert plugin for PrestaShop. See COPYING.md for license details.
 *
 * @author    Scalexpert (https://scalexpert.societegenerale.com/)
 * @copyright Scalexpert
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */


namespace ScalexpertPlugin\Helper;

class Hash
{
    public static function encrypt($value)
    {
        $cipherTool = self::getCipherTool();
        return $cipherTool->encrypt($value);
    }

    public static function decrypt($value)
    {
        $cipherTool = self::getCipherTool();
        return $cipherTool->decrypt($value);
    }

    private static function getCipherTool()
    {
        if (version_compare(_PS_VERSION_, '1.7', '>=')) {
            $cipherTool = new \PhpEncryption(_NEW_COOKIE_KEY_);
        } else if (
            !\Configuration::get('PS_CIPHER_ALGORITHM')
            || !defined('_RIJNDAEL_KEY_')
        ) {
            $cipherTool = new \Blowfish(_COOKIE_KEY_, _COOKIE_IV_);
        } else {
            $cipherTool = new \Rijndael(_RIJNDAEL_KEY_, _RIJNDAEL_IV_);
        }

        return $cipherTool;
    }
}
