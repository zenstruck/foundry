<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Tests\WebTestCase;

use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\Attributes\DependsOnClass;
use PHPUnit\Framework\Attributes\RequiresPhpunit;
use PHPUnit\Framework\Attributes\RequiresPhpunitExtension;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Foundry\Attribute\ResetDatabase;
use Zenstruck\Foundry\PHPUnit\FoundryExtension;
use Zenstruck\Foundry\Tests\Fixture\Factories\Entity\GenericEntityFactory;
use Zenstruck\Foundry\Tests\Integration\RequiresORM;

/**
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 * @requires PHPUnit >=11
 */
#[ResetDatabase]
#[RequiresPhpunit('>=11')]
#[RequiresPhpunitExtension(FoundryExtension::class)]
final class GetWebTestClientWithResetDatabaseAttributeIsNotBrokenTest extends WebTestCase
{
    use RequiresORM;

    /**
     * @test
     * @depends \Zenstruck\Foundry\Tests\WebTestCase\NoResetGetWebTestClientIsNotBrokenTest::class
     */
    #[Test]
    #[DependsOnClass(NoResetGetWebTestClientIsNotBrokenTest::class)]
    public function boots_kernel_and_get_client(): void
    {
        $client = self::createClient();

        $object = GenericEntityFactory::createOne();
        $client->request('GET', "/orm/update/{$object->id}");
        self::assertResponseIsSuccessful();
    }

    /**
     * @test
     * @depends boots_kernel_and_get_client
     */
    #[Test]
    #[Depends('boots_kernel_and_get_client')]
    public function assert_test_starts_with_a_non_booted_kernel(): void
    {
        self::assertFalse(self::$booted);

        // ensure we can get a client without the error:
        // Booting the kernel before calling "WebTestCase::createClient()" is not supported
        self::createClient();
    }
}
