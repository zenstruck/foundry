<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry;

use Zenstruck\Foundry\Persistence\PersistentObjectFactory;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;
use Zenstruck\Foundry\Persistence\Proxy;

/**
 * @template TModel of object
 * @template-extends PersistentProxyObjectFactory<TModel>
 *
 * @method static Proxy[]|TModel[] createMany(int $number, array|callable $attributes = [])
 * @phpstan-method static list<Proxy<TModel>> createMany(int $number, array|callable $attributes = [])
 *
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @deprecated use PersistentProxyObjectFactory instead
 */
abstract class ModelFactory extends PersistentProxyObjectFactory
{
    public function __construct()
    {
        $newFactoryClass = (new \ReflectionClass(static::class()))->isFinal() ? PersistentObjectFactory::class : PersistentProxyObjectFactory::class;

        trigger_deprecation(
            'zenstruck\foundry', '1.37.0',
            \sprintf(
                <<<MESSAGE
                Class "%s" is deprecated and will be removed in version 2.0. Use "%s" instead.
                Be aware that both classes exist "%s" and "%s".
                You should use the first one if you don't need a proxy or if the target class of the factory is final:
                in Foundry 2.0 proxy system will leverage Symfony's lazy object system and it will be impossible to create a proxy for a final class.
                MESSAGE,
                self::class,
                $newFactoryClass,
                PersistentObjectFactory::class,
                PersistentProxyObjectFactory::class
            )
        );

        parent::__construct();
    }

    public static function class(): string
    {
        return static::getClass();
    }

    /**
     * @phpstan-return class-string<TModel>
     *
     * @deprecated use class() instead
     */
    abstract protected static function getClass(): string;

    /**
     * @return mixed[]
     *
     * @deprecated use defaults() instead
     */
    abstract protected function getDefaults(): array;

    protected function defaults(): array|callable
    {
        return $this->getDefaults();
    }
}
