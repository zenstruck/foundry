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

namespace Zenstruck\Foundry\Tests\Integration\Persistence;

use Doctrine\Persistence\ObjectManager;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Configuration;
use Zenstruck\Foundry\Test\Factories;

abstract class PersistenceManagerTestCase extends KernelTestCase
{
    use Factories;

    /**
     * @test
     */
    #[Test]
    public function it_can_test_if_object_with_uuid_is_persisted(): void
    {
        $object = $this->createObject();

        $this->objectManager()->persist($object);

        self::assertFalse(
            Configuration::instance()->persistence()->isPersisted($object)
        );
    }

    abstract protected static function createObject(): object;

    abstract protected static function objectManager(): ObjectManager;
}
