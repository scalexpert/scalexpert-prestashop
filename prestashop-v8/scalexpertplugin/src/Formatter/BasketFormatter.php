<?php

namespace ScalexpertPlugin\Formatter;

class BasketFormatter
{
    public static function normalizeItem(
        array $product,
        $model,
        $characteristics,
        $defaultCategory,
        $manufacturerName,
        \Order $order
    ): array
    {
        $orderCurrency = \Currency::getIsoCodeById((int)$order->id_currency);

        return [
            'id' => (string)$product['id_product'] ?: 'NC',
            'quantity' => (int)$product['product_quantity'] ?: 1,
            'model' => (string)$product['product_reference'] ?: 'NC',
            'label' => trim(strip_tags(substr($product['product_name'], 0, 30))) ?: 'NC',
            'price' => \Tools::ps_round($product['unit_price_tax_incl'], 2) ?: 0.00,
            'currencyCode' => $orderCurrency ?: 'NC',
            'orderId' => $order->reference,
            'brandName' => (false !== $manufacturerName) ? $manufacturerName : 'NC',
            'description' => !empty($product['description']) ?
                substr(strip_tags($product['description']), 0, 255) : 'NC',
            'specifications' => !empty($characteristics) ? $characteristics : 'NC',
            'category' => !empty($defaultCategory->name) ? substr($defaultCategory->name, 0, 20) : 'NC',
            'sku' => !empty($model) ? $model : 'NC',
            'isFinanced' => true,
        ];
    }
}
