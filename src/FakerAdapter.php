<?php

namespace Zenstruck\Foundry;

use Faker;

/**
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 *
 * @internal
 */
final class FakerAdapter
{
    private static ?int $fakerSeed = null;
    private ?int $forcedFakerSeed;

    private static bool $fakerSeedHasBeenSet = false;

    public function __construct(
        private readonly Faker\Generator $faker,
        ?int $forcedFakerSeedFromConfig = null,
        ?int $forcedFakerSeedFromEnv = null,
        private bool $manageFakerSeed = true,
    ) {
        $this->forcedFakerSeed = $forcedFakerSeedFromEnv ?? $forcedFakerSeedFromConfig;
    }

    public static function fakerSeed(): ?int
    {
        return self::$fakerSeed;
    }

    public function faker(): Faker\Generator
    {
        if (!self::$fakerSeedHasBeenSet) {
            $this->seedFaker();
        }

        return $this->faker;
    }

    public static function resetFakerSeed(?int $forcedFakerSeed = null): void
    {
        self::$fakerSeed = $forcedFakerSeed;
        self::$fakerSeedHasBeenSet = false;
    }

    public static function reset(): void
    {
        self::$fakerSeedHasBeenSet = false;
    }

    private function seedFaker(): void
    {
        // if Foundry does not manage the seed, don't give a random seed
        // this prevents collisions in some edge cases where the end-user does not reset its db on each test
        // see https://github.com/zenstruck/foundry/issues/1069
        self::$fakerSeed ??= ($this->forcedFakerSeed ?? ($this->manageFakerSeed ? \random_int(1, 1000000) : null));

        // prevent data providers to use the same seed as the test suite
        $seed = Configuration::instance()->inADataProvider() ? self::$fakerSeed + 1 : self::$fakerSeed;

        $this->faker->seed($seed);
        self::$fakerSeedHasBeenSet = true;
    }
}
