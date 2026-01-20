<?php

declare(strict_types=1);

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Tests\Integration\Faker;

use PHPUnit\Framework\Attributes\AfterClass;
use PHPUnit\Framework\Attributes\BeforeClass;
use Zenstruck\Foundry\Configuration;

trait ResetFakerTestTrait
{
    private static ?string $savedServerSeed = null;
    private static ?string $savedEnvSeed = null;
    private static ?string $savedGetEnvSeed = null;

    private static ?int $currentSeed = null;

    #[BeforeClass(10)]
    public static function __saveAndResetFakerSeed(): void
    {
        self::$savedServerSeed = $_SERVER['FOUNDRY_FAKER_SEED'] ?? null;
        self::$savedEnvSeed = $_ENV['FOUNDRY_FAKER_SEED'] ?? null;
        self::$savedGetEnvSeed = getenv('FOUNDRY_FAKER_SEED') ?: null;

        $_SERVER['FOUNDRY_FAKER_SEED'] = null;
        $_ENV['FOUNDRY_FAKER_SEED'] = null;
        putenv('FOUNDRY_FAKER_SEED');

        Configuration::resetFakerSeed();
    }

    #[AfterClass(-10)]
    public static function __restoreFakerSeed(): void
    {
        $_SERVER['FOUNDRY_FAKER_SEED'] = self::$savedServerSeed;
        $_ENV['FOUNDRY_FAKER_SEED'] = self::$savedEnvSeed;
        if (self::$savedGetEnvSeed === null) {
            putenv('FOUNDRY_FAKER_SEED');
        } else {
            putenv('FOUNDRY_FAKER_SEED='.self::$savedGetEnvSeed);
        }

        $savedValue = self::$savedServerSeed ?? self::$savedEnvSeed ?? self::$savedGetEnvSeed;
        Configuration::resetFakerSeed($savedValue ? (int) $savedValue : null);
    }
}
