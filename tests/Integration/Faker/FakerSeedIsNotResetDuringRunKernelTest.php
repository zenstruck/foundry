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

use PHPUnit\Framework\Attributes\RequiresPhpunit;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Foundry\Configuration;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;
use Zenstruck\Foundry\Tests\Fixture\Factories\Entity\WithUniqueColumn\WithUniqueColumnFactory;
use Zenstruck\Foundry\Tests\Integration\RequiresORM;

/**
 * @requires PHPUnit >=11.0
 */
#[RequiresPhpunit('>=11.0')]
final class FakerSeedIsNotResetDuringRunKernelTest extends WebTestCase
{
    use Factories, RequiresORM, ResetDatabase;

    #[Test]
    public function faker_seed_is_not_reset_with_kernel_shutdown(): void
    {
        $e1 = WithUniqueColumnFactory::createOne();
        self::assertSame(1234, Configuration::fakerSeed());

        self::ensureKernelShutdown();

        $e2 = WithUniqueColumnFactory::createOne();

        self::assertSame(1234, Configuration::fakerSeed());
        self::assertNotSame($e1->getUniqueCol(), $e2->getUniqueCol());
    }

    #[Test]
    public function faker_seed_is_not_reset_within_browser_calls(): void
    {
        $client = $this->createClient();

        $e1 = WithUniqueColumnFactory::createOne();
        self::assertSame(1234, Configuration::fakerSeed());

        $client->request('GET', '/hello-world');

        $e2 = WithUniqueColumnFactory::createOne();
        self::assertSame(1234, Configuration::fakerSeed());
        self::assertNotSame($e1->getUniqueCol(), $e2->getUniqueCol());

        $client->request('GET', '/hello-world');

        $e2 = WithUniqueColumnFactory::createOne();
        self::assertSame(1234, Configuration::fakerSeed());
        self::assertNotSame($e1->getUniqueCol(), $e2->getUniqueCol());
    }
}
