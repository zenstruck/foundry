<?php

namespace Zenstruck\Foundry;

use Faker;
use ProxyManager\Proxy\ValueHolderInterface;

/**
 * @see Factory::__construct()
 *
 * @template TObject as object
 * @psalm-param class-string<TObject> $class
 * @psalm-return AnonymousFactory<TObject>
 */
function factory(string $class, $defaultAttributes = []): AnonymousFactory
{
    return new AnonymousFactory($class, $defaultAttributes);
}

/**
 * @see Factory::create()
 *
 * @return Proxy|object
 *
 * @template TObject of object
 * @psalm-param class-string<TObject> $class
 * @psalm-return Proxy<TObject>
 */
function create(string $class, $attributes = []): Proxy
{
    return factory($class)->create($attributes);
}

/**
 * @see Factory::createMany()
 *
 * @return Proxy[]|object[]
 *
 * @template TObject of object
 * @psalm-param class-string<TObject> $class
 * @psalm-return list<Proxy<TObject>>
 */
function create_many(int $number, string $class, $attributes = []): array
{
    return factory($class)->many($number)->create($attributes);
}

/**
 * Instantiate object without persisting.
 *
 * @return Proxy|object "unpersisted" Proxy wrapping the instantiated object
 *
 * @template TObject of object
 * @psalm-param class-string<TObject> $class
 * @psalm-return Proxy<TObject>
 */
function instantiate(string $class, $attributes = []): Proxy
{
    return factory($class)->withoutPersisting()->create($attributes);
}

/**
 * Instantiate X objects without persisting.
 *
 * @return Proxy[]|object[] "unpersisted" Proxy's wrapping the instantiated objects
 *
 * @template TObject of object
 * @psalm-param class-string<TObject> $class
 * @psalm-return list<Proxy<TObject>>
 */
function instantiate_many(int $number, string $class, $attributes = []): array
{
    return factory($class)->withoutPersisting()->many($number)->create($attributes);
}

/**
 * @see Configuration::repositoryFor()
 */
function repository($objectOrClass): RepositoryProxy
{
    return Factory::configuration()->repositoryFor($objectOrClass);
}

/**
 * @see Factory::faker()
 */
function faker(): Faker\Generator
{
    return Factory::faker();
}

/**
 * Enables autorefresh on the model.
 *
 * @param Proxy|ValueHolderInterface $model
 */
function enable_autorefresh(object $model): void
{
    if ($model instanceof Proxy) {
        $model->enableAutoRefresh();

        return;
    }

    if ($model instanceof ValueHolderInterface) {
        $realModel = $model->getWrappedValueHolderValue();
        if (!$realModel) {
            return;
        }

        $realModel->_foundry_autoRefresh = true;
    }
}

/**
 * Disables autorefresh on the model.
 *
 * @param Proxy|ValueHolderInterface $model
 */
function disable_autorefresh(object $model): void
{
    if ($model instanceof Proxy) {
        $model->disableAutoRefresh();

        return;
    }

    if ($model instanceof ValueHolderInterface) {
        $realModel = $model->getWrappedValueHolderValue();
        if (!$realModel) {
            return;
        }

        $realModel->_foundry_autoRefresh = false;
    }
}

/**
 * Ensures "autoRefresh" is disabled when executing $callback. Re-enables
 * "autoRefresh" after executing callback if it was enabled.
 *
 * @param Proxy|ValueHolderInterface $model
 * @param callable                   $callback (Proxy|ValueHolderInterface $model): void
 *
 * @template TModel as Proxy|ValueHolderInterface
 * @psalm-param TModel $model
 * @psalm-param callable(TModel):void $callback
 */
function without_autorefresh(object $model, callable $callback): void
{
    if ($model instanceof Proxy) {
        $model->withoutAutoRefresh($callback);

        return;
    }

    $originalValue = null;
    if ($model instanceof ValueHolderInterface) {
        $realModel = get_real_object($model);

        /** @psalm-suppress NoInterfaceProperties */
        $originalValue = \property_exists($realModel, '_foundry_autoRefresh') ? $realModel->_foundry_autoRefresh : true;
        /** @psalm-suppress NoInterfaceProperties */
        $realModel->_foundry_autoRefresh = false;
    }

    ($callback)($model);

    if (null !== $originalValue) {
        $realModel->_foundry_autoRefresh = $originalValue;
    }
}

function force_set(object $model, string $property, $value): void
{
    force_set_all($model, [$property => $value]);
}

function force_set_all(object $model, array $properties): void
{
    foreach ($properties as $property => $value) {
        Instantiator::forceSet(get_real_object($model), $property, $value);
    }
}

/**
 * @return mixed
 */
function force_get(object $model, string $property)
{
    return Instantiator::forceGet(get_real_object($model), $property);
}

/**
 * @template T of object
 * @psalm-param T|Proxy<T>|ValueHolderInterface<T> $proxyObject
 * @psalm-return T
 *
 * @psalm-suppress InvalidReturnType
 * @psalm-suppress InvalidReturnStatement
 */
function get_real_object(object $proxyObject): object
{
    if ($proxyObject instanceof Proxy) {
        return $proxyObject->object();
    }

    if ($proxyObject instanceof ValueHolderInterface && $valueHolder = $proxyObject->getWrappedValueHolderValue()) {
        return $valueHolder;
    }

    return $proxyObject;
}
