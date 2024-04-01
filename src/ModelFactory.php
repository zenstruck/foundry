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

/**
 * @template TModel of object
 * @template-extends PersistentProxyObjectFactory<TModel>
 *
 * @method static Proxy[]|TModel[] createMany(int $number, array|callable $attributes = [])
 *
 * @phpstan-method Proxy<TModel> create(array|callable $attributes = [])
 * @phpstan-method static Proxy<TModel> createOne(array $attributes = [])
 * @phpstan-method static Proxy<TModel> find(object|array|mixed $criteria)
 * @phpstan-method static Proxy<TModel> findOrCreate(array $attributes)
 * @phpstan-method static Proxy<TModel> first(string $sortedField = 'id')
 * @phpstan-method static Proxy<TModel> last(string $sortedField = 'id')
 * @phpstan-method static Proxy<TModel> random(array $attributes = [])
 * @phpstan-method static Proxy<TModel> randomOrCreate(array $attributes = [])
 *
 * @phpstan-method FactoryCollection<TModel> sequence(iterable<array<string, mixed>>|callable(): iterable<array<string, mixed>> $sequence)
 * @phpstan-method FactoryCollection<TModel> many(int $min, int|null $max = null)
 *
 * @phpstan-method static list<Proxy<TModel>> all()
 * @phpstan-method static list<Proxy<TModel>> createSequence(iterable<array<string, mixed>>|callable(): iterable<array<string, mixed>> $sequence)
 * @phpstan-method static list<Proxy<TModel>> createMany(int $number, array|callable $attributes = [])
 * @phpstan-method static list<Proxy<TModel>> createMany(int $number, array|callable $attributes = [])
 * @phpstan-method static list<Proxy<TModel>> createSequence(array|callable $sequence)
 * @phpstan-method static list<Proxy<TModel>> findBy(array $attributes)
 * @phpstan-method static list<Proxy<TModel>> randomRange(int $min, int $max, array $attributes = [])
 * @phpstan-method static list<Proxy<TModel>> randomSet(int $number, array $attributes = [])
 *
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @deprecated use PersistentProxyObjectFactory instead
 */
abstract class ModelFactory extends PersistentProxyObjectFactory
{
    public function __construct()
    {
        $newFactoryClass = (new \ReflectionClass(static::class()))->isFinal() ? PersistentObjectFactory::class : ObjectFactory::class;

        trigger_deprecation(
            'zenstruck\foundry', '1.38.0',
            <<<MESSAGE
                Class "%s" is deprecated and will be removed in version 2.0. Use "%s" instead.
                Be aware that three factory classes now exist:
                - "%s" should be used for not-persistent objects
                - "%s" creates and stores persisted objects, and directly return them
                - "%s" same as above, but returns a "proxy" version of the object
                MESSAGE,
            self::class,
            $newFactoryClass,
            ObjectFactory::class,
            PersistentObjectFactory::class,
            PersistentProxyObjectFactory::class,
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
