<?php

declare(strict_types=1);

namespace Zenstruck\Foundry\Tests\Integration\Faker;

use PHPUnit\Framework\Attributes\AfterClass;
use PHPUnit\Framework\Attributes\BeforeClass;
use Zenstruck\Foundry\Configuration;

trait FakerTestTrait
{
    private static int|null $currentSeed = null;
    private static int|null $savedSeed = null;

    #[BeforeClass(10)]
    public static function __saveAndResetFakerSeed(): void
    {
        self::$savedSeed = Configuration::fakerSeed();

        self::$currentSeed = null;
        Configuration::resetFakerSeed();
    }

    #[AfterClass(-10)]
    public static function __restoreSeed(): void
    {
        Configuration::resetFakerSeed();
        Configuration::fakerSeed(self::$savedSeed);
    }
}
