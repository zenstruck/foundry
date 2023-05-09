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

/**
 * @template TModel of object
 * @template-extends Factory<TModel>
 *
 * @method static Proxy[]|TModel[] createMany(int $number, array|callable $attributes = [])
 * @phpstan-method static list<Proxy<TModel>> createMany(int $number, array|callable $attributes = [])
 *
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @deprecated since 1.32, use "Zenstruck\Foundry\Persistence\PersistentObjectFactory" instead
 */
abstract class ModelFactory extends Factory
{
    public function __construct()
    {
        trigger_deprecation('zenstruck/foundry', '1.32', '"%s" is deprecated and this class will be removed in 2.0, please use "%s" instead.', self::class, PersistentObjectFactory::class);

        parent::__construct(static::getClass());
    }

    /**
     * @phpstan-return list<Proxy<TModel>>
     */
    public static function __callStatic(string $name, array $arguments): array
    {
        if ('createMany' !== $name) {
            throw new \BadMethodCallException(\sprintf('Call to undefined static method "%s::%s".', static::class, $name));
        }

        return static::new()->many($arguments[0])->create($arguments[1] ?? []);
    }

    public static function class(): string
    {
        return static::getClass();
    }

    /** @phpstan-return class-string<TModel> */
    abstract protected static function getClass(): string;
}
