<?php
/**
 * Copyright © Scalexpert.
 * This file is part of Scalexpert plugin for PrestaShop. See COPYING.md for license details.
 *
 * @author    Scalexpert (https://scalexpert.societegenerale.com/)
 * @copyright Scalexpert
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */


namespace ScalexpertPlugin\Model;

use DbQuery;

class ProductField
{
    const TABLE = 'scalexpertplugin_product_field';

    public static function createTable(): bool
    {
        $sql = "CREATE TABLE IF NOT EXISTS `" . _DB_PREFIX_ . self::TABLE . "` (
            `id_product` INT NOT NULL,
            `id_lang` INT NOT NULL,
            `model` TEXT NOT NULL,
            `characteristics` TEXT NOT NULL,
            PRIMARY KEY (`id_product`, `id_lang`)
        )
        COLLATE='utf8mb4_general_ci'
        ;";

        return \Db::getInstance()->execute($sql);
    }

    public static function deleteTable(): bool
    {
        $sql = "DROP TABLE " . _DB_PREFIX_ . self::TABLE;
        return \Db::getInstance()->execute($sql);
    }

    public static function saveData($idProduct, $idLang, $model, $characteristics): bool
    {
        return \Db::getInstance()->insert(self::TABLE, [
            'id_product' => (int)$idProduct,
            'id_lang' => (int)$idLang,
            'model' => $model,
            'characteristics' => $characteristics,
        ], false, true, \Db::REPLACE);
    }

    public static function getAllData($idProduct)
    {
        $query = (new DbQuery())->select('*')
            ->from(self::TABLE)
            ->where('id_product = ' . (int)$idProduct);

        $results = \Db::getInstance()->executeS($query);
        if (!$results) {
            return [];
        }

        return $results;
    }

    public static function getData($idProduct, $idLang)
    {
        $query = (new DbQuery())->select('*')
            ->from(self::TABLE)
            ->where('id_product = ' . (int)$idProduct)
            ->where('id_lang = ' . (int)$idLang);

        $results = \Db::getInstance()->getRow($query);
        if (!$results) {
            return [];
        }

        return $results;
    }
}
