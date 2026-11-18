<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Tests\Unit\Maker\Factory;

use Faker\Factory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Zenstruck\Foundry\Maker\Factory\FakerFormatterGuesser;

final class FakerFormatterGuesserTest extends TestCase
{
    /**
     * @test
     * @dataProvider guessableProperties
     */
    #[Test]
    #[DataProvider('guessableProperties')]
    public function it_guesses_an_existing_formatter_from_the_property_name(string $formatter, string $propertyName, string $type, ?int $length = null): void
    {
        self::assertSame("self::faker()->{$formatter}(),", FakerFormatterGuesser::guess($propertyName, $type, $length));
        self::assertNotNull(Factory::create()->format($formatter));
    }

    public static function guessableProperties(): iterable
    {
        yield ['firstName', 'firstName', 'string'];
        yield ['firstName', 'first_name', 'string'];
        yield ['lastName', 'lastName', 'string'];
        yield ['userName', 'username', 'string'];
        yield ['userName', 'login', 'string'];
        yield ['safeEmail', 'email', 'string'];
        yield ['safeEmail', 'emailAddress', 'string'];
        yield ['phoneNumber', 'phone', 'string'];
        yield ['phoneNumber', 'phoneNumber', 'string'];
        yield ['phoneNumber', 'telephone', 'string'];
        yield ['jobTitle', 'job', 'string'];
        yield ['jobTitle', 'jobTitle', 'string'];
        yield ['company', 'company', 'string'];
        yield ['company', 'companyName', 'string'];
        yield ['address', 'address', 'string'];
        yield ['streetAddress', 'street', 'string'];
        yield ['streetAddress', 'streetAddress', 'string'];
        yield ['city', 'city', 'string'];
        yield ['city', 'cityName', 'string'];
        yield ['city', 'town', 'string'];
        yield ['postcode', 'postcode', 'string'];
        yield ['postcode', 'postalCode', 'string'];
        yield ['postcode', 'zipCode', 'string'];
        yield ['country', 'country', 'string'];
        yield ['country', 'countryName', 'string'];
        yield ['countryCode', 'countryCode', 'string'];
        yield ['currencyCode', 'currencyCode', 'string'];
        yield ['locale', 'locale', 'string'];
        yield ['timezone', 'timezone', 'string'];
        yield ['url', 'url', 'string'];
        yield ['url', 'website', 'string'];
        yield ['latitude', 'latitude', 'float'];
        yield ['latitude', 'lat', 'float'];
        yield ['longitude', 'longitude', 'float'];
        yield ['longitude', 'lng', 'float'];
        yield ['longitude', 'lon', 'float'];

        // doctrine types
        yield ['safeEmail', 'email', 'STRING', 180];
        yield ['safeEmail', 'email', 'TEXT'];
        yield ['safeEmail', 'email', 'ASCII_STRING', 255];
        yield ['latitude', 'latitude', 'FLOAT'];
        yield ['latitude', 'latitude', 'DECIMAL'];

        // the length of the column tells which kind of country code is expected
        yield ['countryCode', 'countryCode', 'STRING', 255];
        yield ['countryISOAlpha3', 'countryCode', 'STRING', 3];
        yield ['countryCode', 'countryCode', 'STRING', 2];
    }

    /**
     * @test
     * @dataProvider notGuessableProperties
     */
    #[Test]
    #[DataProvider('notGuessableProperties')]
    public function it_does_not_guess_a_formatter(string $propertyName, string $type, ?int $length = null): void
    {
        self::assertNull(FakerFormatterGuesser::guess($propertyName, $type, $length));
    }

    public static function notGuessableProperties(): iterable
    {
        yield 'ambiguous name' => ['name', 'string'];
        yield 'currency which is not necessarily a code' => ['currency', 'string'];
        yield 'unknown name' => ['prop1', 'string'];
        yield 'string formatter for an int' => ['postcode', 'int'];
        yield 'string formatter for a float' => ['email', 'float'];
        yield 'float formatter for a string' => ['latitude', 'string'];
        yield 'column too short for a first name' => ['firstName', 'STRING', 10];
        yield 'column too short for a country name' => ['country', 'STRING', 50];
        yield 'country which is not assumed to be a code in a short column' => ['country', 'STRING', 2];
        yield 'column too short for a country code' => ['countryCode', 'STRING', 1];
        yield 'column too short for some locales' => ['locale', 'STRING', 5];
    }
}
