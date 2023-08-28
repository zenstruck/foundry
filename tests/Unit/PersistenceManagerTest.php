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

namespace Unit;

use Doctrine\ORM\Mapping\Entity;
use PHPUnit\Framework\TestCase;
use Zenstruck\Foundry\Persistence\PersistenceManager;
use Zenstruck\Foundry\Tests\Fixtures\Document\ODMPost;
use Zenstruck\Foundry\Tests\Fixtures\Document\ODMUser;
use Zenstruck\Foundry\Tests\Fixtures\Entity\Address;
use Zenstruck\Foundry\Tests\Fixtures\Entity\Post;
use Zenstruck\Foundry\Tests\Fixtures\Object\SomeObject;

class PersistenceManagerTest extends TestCase
{
    /**
     * @dataProvider providePersistableClasses
     * @test
     */
    public function it_can_tell_if_class_can_be_persisted(string $className, bool $canBePersisted): void
    {
        self::assertSame($canBePersisted, PersistenceManager::classCanBePersisted($className));
    }

    public static function providePersistableClasses(): iterable
    {
        yield 'ORM entity can be persisted' => [Post::class, true];
        yield 'ORM entity with annotation can be persisted' => [EntityWithAnnotation::class, true];
        yield 'ODM document can be persisted' => [ODMPost::class, true];
        yield 'ORM embeddable cannot be persisted' => [Address::class, false];
        yield 'ODM embedded cannot be persisted' => [ODMUser::class, false];
        yield 'Basic DTO cannot be persisted' => [SomeObject::class, false];
    }
}

/**
 * @Entity
 */
class EntityWithAnnotation
{
}
