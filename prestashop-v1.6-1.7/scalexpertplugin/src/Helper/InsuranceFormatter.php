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

use ScalexpertPlugin\Model\ProductField;

class InsuranceFormatter
{
    public static function normalizeInsurance(
        \Product $product,
        string $solutionCode
    ): array
    {
        $idLang = \Context::getContext()->language->id;
        $manufacturer = new \Manufacturer((int)$product->id_manufacturer, $idLang);
        $category = new \Category((int)$product->id_category_default, $idLang);
        $productFields = ProductField::getData($product->id, $idLang);

        return  [
            "solutionCode" => $solutionCode,
            "sku" => !empty($product->ean13) ? (string)$product->ean13 : 'NC',
            "merchantItemId" => (string)$product->reference,
            "brand" => \Validate::isLoadedObject($manufacturer) ? trim(strip_tags($manufacturer->name)) : 'NC',
            "model" => !empty($productFields['model']) ? $productFields['model'] : 'NC',
            "title" => !empty($product->name) ? trim(strip_tags($product->name)) : 'NC',
            "description" => !empty($product->description) ? trim(strip_tags($product->description)): 'NC',
            "characteristics" => !empty($productFields['characteristics']) ? $productFields['characteristics'] : 'NC',
            "category" => \Validate::isLoadedObject($category) ? trim(strip_tags($category->name)) : 'NC'
        ];
    }
}
