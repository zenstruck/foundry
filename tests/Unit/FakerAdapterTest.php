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

/**
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 * @requires PHPUnit >=11.0
 */
#[RequiresPhpunit('>=11.0')]
final class FakerAdapterTest extends TestCase
{
    use Factories;

    private ?int $backupSeed;

    protected function setUp(): void
    {
        $this->backupSeed = FakerAdapter::fakerSeed();
        FakerAdapter::resetFakerSeed();
    }

    protected function tearDown(): void
    {
        FakerAdapter::resetFakerSeed($this->backupSeed);
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
