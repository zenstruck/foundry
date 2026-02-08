<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Persistence;

use Zenstruck\Foundry\Configuration;
use Zenstruck\Foundry\Factory;
use Zenstruck\Foundry\Object\Hydrator;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 *
 * @phpstan-import-type Attributes from Factory
 */
final class LazyObjectFactory
{
    private function __construct()
    {
    }

    /**
     * @template T of object
     *
     * @param PersistentObjectFactory<T> $factory
     *
     * @return T
     */
    public static function createAsLazyGhost(PersistentObjectFactory $factory): object
    {
        return (new \ReflectionClass($factory::class()))->newLazyGhost(static function(object $ghost) use ($factory): void {
            if (Configuration::instance()->inADataProvider() && $factory->isPersisting()) {
                throw new \LogicException('Cannot access to a persisted object inside a data provider.');
            }

            $instantiator = $factory->instantiator();

            $factory
                // small hack to instantiate into the ghost object
                ->instantiateWith(
                    static function(array $parameters, string $class) use ($instantiator, $ghost): object {
                        $object = $instantiator($parameters, $class);
                        Hydrator::hydrateFromOtherObject($ghost, $object);

                        return $ghost;
                    }
                )->create();
        });
    }

    /**
     * @template T
     *
     * @param T $what
     *
     * @return T
     */
    public static function unwrap(mixed $what, bool $withAutoRefresh = true): mixed
    {
        if (\is_array($what)) {
            return \array_map(static fn(mixed $w) => self::unwrap($w, $withAutoRefresh), $what); // @phpstan-ignore return.type
        }

        if (
            \is_object($what)
            && ($reflector = new \ReflectionClass($what))->isUninitializedLazyObject($what)
        ) {
            return $reflector->initializeLazyObject($what);
        }

        return $what;
    }
}
