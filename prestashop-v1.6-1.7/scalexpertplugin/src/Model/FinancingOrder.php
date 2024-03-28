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
use Db;

class FinancingOrder
{
    const TABLE = 'scalexpert_order_financing';

    public static function createTable()
    {
        $sql = "CREATE TABLE IF NOT EXISTS  `"._DB_PREFIX_.self::TABLE."` (
            `id_order` INT(10) UNSIGNED NOT NULL,
            `id_subscription` VARCHAR(255) NULL,
            PRIMARY KEY (`id_order`)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;";

        return \Db::getInstance()->execute($sql);
    }

    public static function save($idOrder, $idSubscription)
    {
        return Db::getInstance()->insert(self::TABLE, [
            'id_order' => (int)$idOrder,
            'id_subscription' => pSQL($idSubscription),
        ], false, true, Db::INSERT_IGNORE);
    }

    public static function get($idOrder)
    {
        $query = (new DbQuery())->select('id_subscription')
            ->from(self::TABLE)
            ->where('id_order = '.(int)$idOrder);

        return Db::getInstance()->getValue($query);
    }

    public static function deleteTable()
    {
        $sql = "DROP TABLE "._DB_PREFIX_.self::TABLE;
        return \Db::getInstance()->execute($sql);
    }
}
