<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Tests\Unit;

use Faker;
use PHPUnit\Framework\Attributes\RequiresPhpunit;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Zenstruck\Foundry\FakerAdapter;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Tests\Integration\Faker\ResetFakerTestTrait;

/**
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 * @requires PHPUnit >=11.0.0
 */
#[RequiresPhpunit('>=11.0.0')]
final class FakerAdapterTest extends TestCase
{
    // ResetFakerTestTrait restores the run's seed from the environment, which this test cannot infer
    // from FakerAdapter::fakerSeed(): a generated seed would be restored as an explicitly forced one
    use Factories, ResetFakerTestTrait;

    protected function setUp(): void
    {
        FakerAdapter::resetFakerSeed();
    }

    protected function tearDown(): void
    {
        FakerAdapter::setCurrentTestId(null);
    }

    #[Test]
    public function faker_seed_is_automatically_generated_when_managed(): void
    {
        $adapter = new FakerAdapter(Faker\Factory::create());

        $this->assertNull(FakerAdapter::fakerSeed());

        $adapter->faker();

        $this->assertIsInt(FakerAdapter::fakerSeed());
        $this->assertGreaterThanOrEqual(1, FakerAdapter::fakerSeed());
        $this->assertLessThanOrEqual(1000000, FakerAdapter::fakerSeed());
    }

    #[Test]
    public function faker_seed_is_not_generated_when_not_managed(): void
    {
        $adapter = new FakerAdapter(Faker\Factory::create(), manageFakerSeed: false);

        $this->assertNull(FakerAdapter::fakerSeed());

        $adapter->faker();

        $this->assertNull(FakerAdapter::fakerSeed());
    }

    #[Test]
    public function forced_seed_is_used_even_when_seed_is_not_managed(): void
    {
        $adapter = new FakerAdapter(Faker\Factory::create(), forcedFakerSeedFromEnv: 4321, manageFakerSeed: false);

        $adapter->faker();

        $this->assertSame(4321, FakerAdapter::fakerSeed());
    }

    #[Test]
    public function faker_is_left_untouched_when_seed_is_not_managed_but_was_already_generated(): void
    {
        // simulates a unit test booted by UnitTestConfig, which cannot know about the bundle's "manage_seed" config
        (new FakerAdapter(Faker\Factory::create()))->faker();
        FakerAdapter::reset();

        $this->assertIsInt(FakerAdapter::fakerSeed());

        $faker = Faker\Factory::create();
        $faker->seed(4321);
        $expected = $faker->numberBetween(1, \PHP_INT_MAX);
        $faker->seed(4321);

        (new FakerAdapter($faker, manageFakerSeed: false))->faker();

        $this->assertSame($expected, $faker->numberBetween(1, \PHP_INT_MAX));
    }

    #[Test]
    public function forced_seed_stays_in_effect_for_the_whole_run_when_seed_is_not_managed(): void
    {
        (new FakerAdapter(Faker\Factory::create(), forcedFakerSeedFromEnv: 4321, manageFakerSeed: false))->faker();
        FakerAdapter::reset();

        $faker = Faker\Factory::create();
        $faker->seed(4321);
        $expected = $faker->numberBetween(1, \PHP_INT_MAX);
        $faker->seed(1); // move the rng away from the forced seed

        // this adapter has no forced seed of its own: the seed forced earlier in the run must still be applied
        (new FakerAdapter($faker, manageFakerSeed: false))->faker();

        $this->assertSame(4321, FakerAdapter::fakerSeed());
        $this->assertSame($expected, $faker->numberBetween(1, \PHP_INT_MAX));
    }

    #[Test]
    public function each_test_derives_its_own_reproducible_seed_from_the_run_seed(): void
    {
        $faker = Faker\Factory::create();

        $wordFor = static function(string $testId) use ($faker): string {
            FakerAdapter::reset();
            FakerAdapter::setCurrentTestId($testId);
            (new FakerAdapter($faker, forcedFakerSeedFromEnv: 1234))->faker();

            return $faker->word();
        };

        $first = $wordFor('App\\FooTest::first');
        $second = $wordFor('App\\FooTest::second');

        $this->assertSame(1234, FakerAdapter::fakerSeed(), 'the run seed is left untouched');
        $this->assertNotSame($first, $second, 'two tests do not generate the same values');
        $this->assertSame($first, $wordFor('App\\FooTest::first'), 'a given test replays identically');
    }

    #[Test]
    public function forced_seed_from_config_is_used(): void
    {
        $adapter = new FakerAdapter(Faker\Factory::create(), forcedFakerSeedFromConfig: 12345);

        $adapter->faker();

        $this->assertSame(12345, FakerAdapter::fakerSeed());
    }

    #[Test]
    public function forced_seed_from_env_takes_precedence_over_config(): void
    {
        $adapter = new FakerAdapter(
            Faker\Factory::create(),
            forcedFakerSeedFromConfig: 12345,
            forcedFakerSeedFromEnv: 99999,
        );

        $adapter->faker();

        $this->assertSame(99999, FakerAdapter::fakerSeed());
    }

    #[Test]
    public function reset_faker_seed_resets_state(): void
    {
        $adapter = new FakerAdapter(Faker\Factory::create());

        $adapter->faker();
        $firstSeed = FakerAdapter::fakerSeed();

        FakerAdapter::resetFakerSeed();

        $this->assertNull(FakerAdapter::fakerSeed());

        $adapter->faker();

        $this->assertNotNull(FakerAdapter::fakerSeed());
        $this->assertNotSame($firstSeed, FakerAdapter::fakerSeed());
    }

    #[Test]
    public function reset_faker_seed_can_set_forced_seed(): void
    {
        $adapter = new FakerAdapter(Faker\Factory::create());

        $adapter->faker();

        FakerAdapter::resetFakerSeed(42);

        $this->assertSame(42, FakerAdapter::fakerSeed());

        $adapter->faker();

        $this->assertSame(42, FakerAdapter::fakerSeed());
    }
}
