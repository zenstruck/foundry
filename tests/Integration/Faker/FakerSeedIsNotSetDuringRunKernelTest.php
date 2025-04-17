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
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;
use Zenstruck\Foundry\Tests\Fixture\Factories\Entity\WithUniqueColumn\WithUniqueColumnFactory;
use Zenstruck\Foundry\Tests\Integration\RequiresORM;

/**
 * @requires PHPUnit >=11.0
 */
#[RequiresPhpunit('>=11.0')]
final class FakerSeedIsNotSetDuringRunKernelTest extends KernelTestCase
{
    use Factories, FakerTestTrait, RequiresORM, ResetDatabase;

    #[Test]
    public function faker_seed_is_no_set_during_run(): void
    {
        if (!\getenv('MONGO_URL')) {
            self::markTestSkipped('doctrine/odm not enabled.');
        }

        self::bootKernel();

        $e1 = WithUniqueColumnFactory::createOne();

        self::ensureKernelShutdown();

        $e2 = WithUniqueColumnFactory::createOne();

        $this->assertNotSame($e1->getUniqueCol(), $e2->getUniqueCol());
    }
}
