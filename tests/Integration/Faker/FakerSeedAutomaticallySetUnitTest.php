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

use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\Attributes\RequiresPhpunit;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Zenstruck\Foundry\FakerAdapter;
use Zenstruck\Foundry\Test\Factories;
use function Zenstruck\Foundry\faker;

/**
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 * @requires PHPUnit >=11.0
 */
#[RequiresPhpunit('>=11.0')]
final class FakerSeedAutomaticallySetUnitTest extends TestCase
{
    use Factories, ResetFakerTestTrait;

    #[Test]
    public function faker_seed_does_not_change(): void
    {
        faker(); // triggers seeding

        self::$currentSeed = FakerAdapter::fakerSeed();

        self::assertSame(self::$currentSeed, FakerAdapter::fakerSeed());
    }

    #[Test]
    #[Depends('faker_seed_does_not_change')]
    public function faker_seed_does_not_change_between_tests(): void
    {
        self::assertSame(self::$currentSeed, FakerAdapter::fakerSeed());
    }
}
