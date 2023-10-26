<?php

namespace ScalexpertPlugin\Helper;

use DbQuery;
use Db;

class FinancingOrder
{
    const table = 'scalexpert_order_financing';

    public static function save($idOrder, $idSubscription)
    {
        return Db::getInstance()->insert(self::table, [
            'id_order' => (int)$idOrder,
            'id_subscription' => pSQL($idSubscription),
        ], false, true, Db::INSERT_IGNORE);
    }

    public static function get($idOrder)
    {
        $query = (new DbQuery())->select('id_subscription')
            ->from(self::table)
            ->where('id_order = '.(int)$idOrder);

        return Db::getInstance()->getValue($query);
    }
}