<?php
/**
 * Copyright © Scalexpert.
 * This file is part of Scalexpert plugin for PrestaShop. See COPYING.md for license details.
 *
 * @author    Scalexpert (https://scalexpert.societegenerale.com/)
 * @copyright Scalexpert
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

declare(strict_types=1);

namespace ScalexpertPlugin\Formatter;

class BuyerFormatter
{
    public static function normalizeContact(\Address $address, \Customer $customer): array
    {
        if (!empty($customer->id_gender) && $customer->id_gender == 1) {
            $genderName = 'MR';
        } else {
            $genderName = 'MRS';
        }

        return [
            'lastName' => $address->lastname,
            'firstName' => $address->firstname,
            'commonTitle' => $genderName,
            'email' => $customer->email,
            'mobilePhoneNumber' => static::formatPhone(
                !empty($address->phone_mobile) ? $address->phone_mobile : $address->phone,
                $address->id_country
            ),
            'professionalTitle' => '',
        ];
    }

    public static function normalizeAddress(\Address $address, string $locationType): array
    {
        $countryCode = \Country::getIsoById($address->id_country);

        return [
            'locationType' => $locationType,
            'streetNumberSuffix' => '',
            'streetName' => $address->address1,
            'streetNameComplement' => $address->address2,
            'zipCode' => $address->postcode ?: 'NC',
            'cityName' => $address->city ?: 'NC',
            'regionName' => (string)$address->id_state ?: 'NC',
            'countryCode' => (string)$countryCode ?: 'NC',
        ];
    }

    public static function normalizeBuyer(\Customer $customer, \Address $address): array
    {
        $countryCode = \Country::getIsoById($address->id_country);

        return [
            'contact' => [
                'lastName' => $customer->lastname ?: '',
                'firstName' => $customer->firstname ?: '',
                'email' => $customer->email ?: '',
                'mobilePhoneNumber' => static::formatPhone(
                    !empty($address->phone_mobile) ? $address->phone_mobile : $address->phone,
                    $address->id_country
                ),
                'phoneNumber' => static::formatPhone(
                    !empty($address->phone_mobile) ? $address->phone_mobile : $address->phone,
                    $address->id_country
                ),
            ],
            'address' => [
                'streetNumber' => 0,
                'streetNumberSuffix' => '',
                'streetName' => $address->address1 ?: '',
                'streetNameComplement' => $address->address2 ?: '',
                'zipCode' => $address->postcode ?: 'NC',
                'cityName' => $address->city ?: 'NC',
                'regionName' => (string)$address->id_state ?: 'NC',
                'countryCode' => (string)$countryCode ?: 'NC',
            ]
        ];
    }

    protected static function formatPhone($phoneNumber, $idCountry): string
    {
        if (
            !empty($phoneNumber)
            && !str_starts_with($phoneNumber, '+')
        ) {
            $addressCountry = new \Country($idCountry);

            if (!empty($addressCountry->call_prefix)) {
                $phoneNumber = sprintf('+%s%s', $addressCountry->call_prefix, substr($phoneNumber, 1));
            } else {
                $phoneNumber = sprintf('+33%s', substr($phoneNumber, 1));
            }
        }

        return $phoneNumber;
    }
}
