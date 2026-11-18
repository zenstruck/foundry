<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Maker\Factory;

/**
 * Guesses a Faker formatter from the name of a property, when its type alone would only give a meaningless value.
 *
 * @internal
 */
final class FakerFormatterGuesser
{
    /**
     * @param string $type PHP or Doctrine type of the property
     *
     * @return string|null The code of the default value
     */
    public static function guess(string $propertyName, string $type, ?int $length = null): ?string
    {
        $name = \str_replace('_', '', \mb_strtolower($propertyName));

        $formatter = match (\mb_strtolower($type)) {
            'string', 'text', 'ascii_string' => self::stringFormatter($name, $length),
            'float', 'decimal' => self::floatFormatter($name),
            default => null,
        };

        return $formatter ? "self::faker()->{$formatter}()," : null;
    }

    private static function stringFormatter(string $name, ?int $length): ?string
    {
        // each formatter comes with the length a column needs to store any of its values, whatever the locale
        [$formatter, $minLength] = match ($name) {
            'firstname' => ['firstName', 32],
            'lastname' => ['lastName', 50],
            'username', 'login' => ['userName', 64],
            'email', 'emailaddress' => ['safeEmail', 100],
            'phone', 'phonenumber', 'telephone' => ['phoneNumber', 32],
            'job', 'jobtitle' => ['jobTitle', 100],
            'company', 'companyname' => ['company', 100],
            'address' => ['address', 255],
            'street', 'streetaddress' => ['streetAddress', 100],
            'city', 'cityname', 'town' => ['city', 64],
            'postcode', 'postalcode', 'zipcode' => ['postcode', 10],
            'country', 'countryname' => ['country', 100],
            'countrycode' => 3 === $length ? ['countryISOAlpha3', 3] : ['countryCode', 2],
            'currencycode' => ['currencyCode', 3],
            'locale' => ['locale', 6],
            'timezone' => ['timezone', 32],
            'url', 'website' => ['url', 255],
            default => [null, 0],
        };

        return null === $length || $length >= $minLength ? $formatter : null;
    }

    private static function floatFormatter(string $name): ?string
    {
        return match ($name) {
            'latitude', 'lat' => 'latitude',
            'longitude', 'lng', 'lon' => 'longitude',
            default => null,
        };
    }
}
