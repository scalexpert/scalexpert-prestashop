<?php

namespace ScalexpertPlugin\Helper;

class BuyerFormatter
{
    public static function normalizeCancelContact(
        \Customer $customer,
        \Address $address
    ): array
    {
        return [
            "lastName" => $customer->lastname,
            "firstName" => $customer->firstname,
            "email" => $customer->email,
            "phoneNumber" => self::formatPhone(
                !empty($address->phone) ? $address->phone : $address->phone_mobile,
                $address->id_country
            ),
        ];
    }

    public static function normalizeContact(
        \Address $address,
        \Customer $customer
    ): array
    {
        $genderName = self::formatGender($customer->id_gender);

        return [
            "lastName" => $address->lastname,
            "firstName" => $address->firstname,
            "commonTitle" => $genderName,
            "email" => $customer->email,
            "mobilePhoneNumber" => self::formatPhone(
                !empty($address->phone_mobile) ? $address->phone_mobile : $address->phone,
                $address->id_country
            ),
            "professionalTitle" => '',
            /*"phoneNumber" => self::formatPhone(
                $address->phone,
                $address->id_country
            ),*/
        ];
    }

    public static function normalizeInsuranceContact(
        \Address $address,
        \Customer $customer
    ): array
    {
        return [
            "lastName" => $address->lastname,
            "firstName" => $address->firstname,
            "email" => $customer->email,
            "mobilePhoneNumber" => self::formatPhone(
                !empty($address->phone_mobile) ? $address->phone_mobile : $address->phone,
                $address->id_country
            ),
            "phoneNumber" => '',
        ];
    }

    public static function normalizeAddress(
        \Address $address,
        string $locationType
    ): array
    {
        $countryCode = \Country::getIsoById($address->id_country);

        return [
            "locationType" => $locationType,
//            "streetNumber" => 0,
            "streetNumberSuffix" => '',
            "streetName" => (string)$address->address1,
            "streetNameComplement" => (string)$address->address2,
            "zipCode" => (string)$address->postcode ?: 'NC',
            "cityName" => (string)$address->city ?: 'NC',
            "regionName" => (string)$address->id_state ?: 'NC',
            "countryCode" => (string)$countryCode ?: 'NC',
        ];
    }

    public static function normalizeInsuranceAddress(
        \Address $address
    ): array
    {
        $countryCode = \Country::getIsoById($address->id_country);

        return [
            "streetNumber" => 0,
            "streetNumberSuffix" => '',
            "streetName" => (string)$address->address1,
            "streetNameComplement" => (string)$address->address2,
            "zipCode" => (string)$address->postcode ?: 'NC',
            "cityName" => (string)$address->city ?: 'NC',
            "regionName" => (string)$address->id_state ?: 'NC',
            "countryCode" => (string)$countryCode ?: 'NC',
        ];
    }

    protected static function formatPhone(
        string $phoneNumber,
        int $idCountry
    ): string
    {
        if (
            !empty($phoneNumber)
            && strpos($phoneNumber, '+') === false
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

    protected static function formatGender(
        int $genderId
    ): string
    {
        return 1 === $genderId ? 'MR' : 'MRS';
    }
}
