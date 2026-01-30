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

namespace Zenstruck\Foundry\Tests\Integration\ForceFactoriesTraitUsage;

use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\RequiresPhpunit;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;
use Zenstruck\Foundry\Tests\Fixture\Factories\Entity\GenericEntityFactory;
use Zenstruck\Foundry\Tests\Fixture\Factories\Object1Factory;
use Zenstruck\Foundry\Tests\Integration\RequiresORM;

#[RequiresPhpunit('>=11.0')]
final class WebTestCaseWithBothTraitsInWrongOrderTest extends WebTestCase
{
    // traits are in a different order than usual on purpose (ResetDatabase must be first)
    use ResetDatabase, Factories, RequiresORM;

    #[Test]
    public function should_not_throw(): void
    {
        Object1Factory::createOne();

        $this->expectNotToPerformAssertions();
    }

    #[Test]
    public function should_not_throw_even_when_kernel_is_booted(): void
    {
        self::getContainer()->get('.zenstruck_foundry.configuration');

        Object1Factory::createOne();

        $this->expectNotToPerformAssertions();
    }

    /**
     * @test
     */
    #[Test]
    public function ensure_entity_manager_instance_is_shared(): void
    {
        static::createClient();

        $object = GenericEntityFactory::createOne();

        /** @var EntityManagerInterface $em */
        $em = self::getContainer()->get(EntityManagerInterface::class);

        self::assertTrue(
            $em->contains($object)
        );
    }
}
