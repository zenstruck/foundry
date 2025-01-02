<?php

declare(strict_types=1);

namespace Zenstruck\Foundry\Tests\Integration\Persistence;

use Doctrine\Persistence\ObjectManager;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Configuration;
use Zenstruck\Foundry\Test\Factories;

abstract class PersistenceManagerTestCase extends KernelTestCase
{
    use Factories;

    /**
     * @test
     */
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
