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

namespace Zenstruck\Foundry\Tests\Unit\Persistence;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Zenstruck\Foundry\Persistence\LazyObjectFactory;

/**
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 */
final class LazyObjectFactoryTest extends TestCase
{
    #[Test]
    public function unwrap_returns_regular_object_as_is(): void
    {
        $object = new \stdClass();

        self::assertSame($object, LazyObjectFactory::unwrap($object));
    }

    #[Test]
    public function unwrap_returns_scalar_as_is(): void
    {
        self::assertSame('foo', LazyObjectFactory::unwrap('foo'));
        self::assertSame(42, LazyObjectFactory::unwrap(42));
        self::assertNull(LazyObjectFactory::unwrap(null));
    }

    #[Test]
    public function unwrap_processes_array_recursively(): void
    {
        $object = new \stdClass();
        $result = LazyObjectFactory::unwrap([$object, 'foo', 42]);

        self::assertSame([$object, 'foo', 42], $result);
    }

    #[Test]
    public function unwrap_initializes_lazy_ghost(): void
    {
        $ghost = (new \ReflectionClass(LazyGhostSubject::class))->newLazyGhost(static function(LazyGhostSubject $ghost): void {
            $ghost->value = 'initialized';
        });

        $reflector = new \ReflectionClass($ghost);
        self::assertTrue($reflector->isUninitializedLazyObject($ghost));

        $result = LazyObjectFactory::unwrap($ghost);

        self::assertSame($ghost, $result);
        self::assertFalse($reflector->isUninitializedLazyObject($ghost));
        self::assertSame('initialized', $ghost->value);
    }

    #[Test]
    public function unwrap_initializes_lazy_ghosts_in_array(): void
    {
        $ghost = (new \ReflectionClass(LazyGhostSubject::class))->newLazyGhost(static function(LazyGhostSubject $ghost): void {
            $ghost->value = 'initialized';
        });

        $result = LazyObjectFactory::unwrap([$ghost]);

        self::assertSame('initialized', $result[0]->value);
    }

    #[Test]
    public function unwrap_does_not_reinitialize_already_initialized_object(): void
    {
        $ghost = (new \ReflectionClass(LazyGhostSubject::class))->newLazyGhost(static function(LazyGhostSubject $ghost): void {
            $ghost->value = 'initialized';
        });

        // Force initialization
        $_ = $ghost->value;

        self::assertFalse((new \ReflectionClass($ghost))->isUninitializedLazyObject($ghost));

        $result = LazyObjectFactory::unwrap($ghost);

        self::assertSame($ghost, $result);
        self::assertSame('initialized', $result->value);
    }
}

class LazyGhostSubject
{
    public string $value = 'default';
}
