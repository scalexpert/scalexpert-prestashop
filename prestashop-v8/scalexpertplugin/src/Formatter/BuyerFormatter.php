<?php
/**
 * Copyright © Scalexpert.
 * This file is part of Scalexpert plugin for PrestaShop.
 *
 * @author    Société Générale
 * @copyright Scalexpert
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
            'mobilePhoneNumber' => self::formatPhone(
                !empty($address->phone_mobile) ? $address->phone_mobile : $address->phone,
                $address->id_country
            ),
            'professionalTitle' => '',
            // 'phoneNumber' => self::formatPhone($address->phone, $address->id_country),
        ];
    }

    public static function normalizeAddress(\Address $address, string $locationType): array
    {
        $countryCode = \Country::getIsoById($address->id_country);

        return [
            'locationType' => $locationType,
            // 'streetNumber' => 0,
            'streetNumberSuffix' => '',
            'streetName' => (string) $address->address1,
            'streetNameComplement' => (string) $address->address2,
            'zipCode' => (string) $address->postcode ?: 'NC',
            'cityName' => (string) $address->city ?: 'NC',
            'regionName' => (string) $address->id_state ?: 'NC',
            'countryCode' => (string) $countryCode ?: 'NC',
        ];
    }

    protected static function formatPhone($phoneNumber, $idCountry): string
    {
        if (!empty($phoneNumber) && strpos($phoneNumber, '+') === false) {
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