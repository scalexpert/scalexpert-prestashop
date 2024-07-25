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

use Db;
use DbQuery;

class CartInsurance
{
    const TABLE = 'scalexpertplugin_cart_insurance';

    public static function createTable(): bool
    {
        $sql = "CREATE TABLE IF NOT EXISTS  `" . _DB_PREFIX_ . self::TABLE . "` (
            `id_cart_insurance` INT AUTO_INCREMENT,
            `id_product` INT,
            `id_product_attribute` INT,
            `quotations` VARCHAR(1024) NULL,
            `subscriptions` VARCHAR(1024) NULL,
            `id_insurance_product` INT,
            `id_cart` INT,
            `id_item` VARCHAR(128),
            `id_insurance` VARCHAR(128),
            `solution_code` VARCHAR(128),
            `subscriptions_processed` BOOLEAN DEFAULT FALSE NULL,
            PRIMARY KEY (`id_cart_insurance`)
        )
        COLLATE='utf8mb4_general_ci';";

        return \Db::getInstance()->execute($sql);
    }

    public static function deleteTable(): bool
    {
        $sql = "DROP TABLE " . _DB_PREFIX_ . self::TABLE;
        return \Db::getInstance()->execute($sql);
    }

    public static function addInsuranceLine($params): bool
    {
        $params = static::formatParams($params);

        return Db::getInstance()->insert(static::TABLE, [
            'id_cart' => $params['idCart'],
            'id_product' => $params['idProduct'],
            'id_product_attribute' => $params['idProductAttribute'],
            'id_item' => $params['idItem'],
            'id_insurance' => $params['idInsurance'],
            'solution_code' => $params['solutionCode'],
            'id_insurance_product' => $params['idInsuranceProduct']
        ]);
    }

    public static function updateInsuranceLine($idCartInsurance, $params): bool
    {
        $params = static::formatParams($params);

        return Db::getInstance()->update(static::TABLE, [
            'id_cart' => $params['idCart'],
            'id_product' => $params['idProduct'],
            'id_product_attribute' => $params['idProductAttribute'],
            'id_item' => $params['idItem'],
            'id_insurance' => $params['idInsurance'],
            'solution_code' => $params['solutionCode'],
            'id_insurance_product' => $params['idInsuranceProduct'],
            'quotations' => $params['quotes'],
        ], 'id_cart_insurance = ' . (int)$idCartInsurance);
    }

    private static function formatParams($params): array
    {
        return array_merge(
            [
                'idCart' => '',
                'idProduct' => '',
                'idProductAttribute' => '',
                'idItem' => '',
                'idInsurance' => '',
                'solutionCode' => '',
                'idInsuranceProduct' => '',
                'quotes' => null,
            ],
            $params
        );
    }

    public static function removeInsuranceLine($idCart, $idProduct, $idProductAttribute): bool
    {
        return Db::getInstance()->delete(static::TABLE,
            'id_cart = ' . (int)$idCart
            . ' AND id_product = ' . (int)$idProduct
            . ' AND id_product_attribute = ' . (int)$idProductAttribute
        );
    }

    public static function getInsuranceLine($idCart, $idProduct, $idProductAttribute)
    {
        $query = (new DbQuery())->select('*')
            ->from(self::TABLE)
            ->where('id_cart = ' . (int)$idCart)
            ->where('id_product = ' . (int)$idProduct);

        if (null !== $idProductAttribute) {
            $query->where('id_product_attribute = ' . (int)$idProductAttribute);
        }

        $results = \Db::getInstance()->getRow($query);
        if (!$results) {
            return [];
        }

        return $results;
    }

    public static function getInsuranceLineByCartAndInsuranceId($idCart, $idInsuranceProduct)
    {
        $query = (new DbQuery())->select('*')
            ->from(self::TABLE)
            ->where('id_cart = ' . (int)$idCart)
            ->where('id_insurance_product = ' . (int)$idInsuranceProduct);

        $results = \Db::getInstance()->getRow($query);
        if (!$results) {
            return [];
        }

        return $results;
    }

    public static function getInsuranceLineByInsuranceId($idCart, $idItem, $idInsurance)
    {
        $query = (new DbQuery())->select('*')
            ->from(self::TABLE)
            ->where('id_cart = ' . (int)$idCart)
            ->where('id_item = "' . $idItem . '"')
            ->where('id_insurance = ' . (int)$idInsurance);

        $results = \Db::getInstance()->getRow($query);
        if (!$results) {
            return [];
        }

        return $results;
    }

    public static function getInsuranceByIdCart($idCart)
    {
        $query = (new DbQuery())->select('*')
            ->from(self::TABLE)
            ->where('id_cart = ' . (int)$idCart);

        $results = \Db::getInstance()->executeS($query);
        if (!$results) {
            return [];
        }

        return $results;
    }

    public static function updateSubscriptionsProcessed($idCartInsurance, $processed): bool
    {
        return Db::getInstance()->update(static::TABLE, [
            'subscriptions_processed' => (bool)$processed,
        ], 'id_cart_insurance = ' . (int)$idCartInsurance);
    }

    public static function updateInsuranceQuotations($idCartInsurance, $quotations): bool
    {
        if (is_array($quotations)) {
            $quotations = json_encode($quotations);
        }

        return Db::getInstance()->update(static::TABLE, [
            'quotations' => (string)$quotations,
        ], 'id_cart_insurance = ' . (int)$idCartInsurance);
    }

    public static function getInsuranceQuotations($idCartInsurance)
    {
        $query = (new DbQuery())->select('quotations')
            ->from(self::TABLE)
            ->where('id_cart_insurance = ' . (int)$idCartInsurance);

        $results = \Db::getInstance()->getValue($query);
        return !empty($results) ? json_decode($results, true) : [];
    }

    public static function updateInsuranceSubscriptions($idCartInsurance, $subscriptions): bool
    {
        if (is_array($subscriptions)) {
            $subscriptions = json_encode($subscriptions);
        }

        return Db::getInstance()->update(static::TABLE, [
            'subscriptions' => (string)$subscriptions,
        ], 'id_cart_insurance = ' . (int)$idCartInsurance);
    }

    public static function getInsuranceSubscriptions($idCartInsurance)
    {
        $query = (new DbQuery())->select('subscriptions')
            ->from(self::TABLE)
            ->where('id_cart_insurance = ' . (int)$idCartInsurance);

        $results = \Db::getInstance()->getValue($query);
        return !empty($results) ? json_decode($results, true) : [];
    }
}
