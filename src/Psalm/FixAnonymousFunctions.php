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

namespace Zenstruck\Foundry\Psalm;

use Psalm\Plugin\EventHandler\Event\FunctionReturnTypeProviderEvent;
use Psalm\Plugin\EventHandler\Event\MethodReturnTypeProviderEvent;
use Psalm\Plugin\EventHandler\FunctionReturnTypeProviderInterface;
use Psalm\Plugin\EventHandler\MethodReturnTypeProviderInterface;
use Psalm\Type\Union;
use Zenstruck\Foundry\Object\ObjectFactory;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;
use Zenstruck\Foundry\Proxy;

final class FixAnonymousFunctions implements FunctionReturnTypeProviderInterface, MethodReturnTypeProviderInterface
{
    public static function getFunctionIds(): array
    {
        return [
            'zenstruck\\foundry\\anonymous',
            'zenstruck\\foundry\\create',
            'zenstruck\\foundry\\create_many',
        ];
    }

    public static function getFunctionReturnType(FunctionReturnTypeProviderEvent $event): ?Union
    {
        return match ($event->getFunctionId()) {
            'zenstruck\\foundry\\anonymous' => self::getAnonymousReturnType($event),
            'zenstruck\\foundry\\create' => self::getCreateReturnType($event),
            'zenstruck\\foundry\\create_many' => self::getCreateManyReturnType($event),
            default => null,
        };
    }

    public static function getClassLikeNames(): array
    {
        return [PersistentObjectFactory::class];
    }

    // anonymous(Entity::class)->many() returns a FactoryCollection<Proxy<Entity>>
    // anonymous(Entity::class)->sequence() returns a FactoryCollection<Proxy<Entity>>
    public static function getMethodReturnType(MethodReturnTypeProviderEvent $event): ?Union
    {
        if (!\in_array($event->getMethodNameLowercase(), ['many', 'sequence'], true)) {
            return null;
        }

        return PsalmTypeHelper::factoryCollection($event->getTemplateTypeParameters()[0]);
    }

    // anonymous(Entity::class) returns a FactoryCollection<Proxy<Entity>>
    // anonymous(Object::class) returns a FactoryCollection<Object>
    private static function getAnonymousReturnType(FunctionReturnTypeProviderEvent $event): ?Union
    {
        $targetClass = PsalmTypeHelper::resolveFactoryTargetClass($event->getCallArgs()[0] ?? null);

        $factoryClass = match (PsalmTypeHelper::isFactoryTargetClassPersisted($targetClass)) {
            true => PersistentObjectFactory::class,
            false => ObjectFactory::class,
            null => null
        };

        if (!$factoryClass) {
            return null;
        }

        return PsalmTypeHelper::genericType($factoryClass, $targetClass);
    }

    // create(Entity::class) returns a Proxy<Entity>
    // create(Object::class) returns a Object
    private static function getCreateReturnType(FunctionReturnTypeProviderEvent $event): ?Union
    {
        $targetClass = PsalmTypeHelper::resolveFactoryTargetClass($event->getCallArgs()[0] ?? null);

        return match (PsalmTypeHelper::isFactoryTargetClassPersisted($targetClass)) {
            true => PsalmTypeHelper::genericType(Proxy::class, $targetClass),
            false => PsalmTypeHelper::classType($targetClass),
            null => null
        };
    }

    // create(Entity::class) returns a list<Proxy<Entity>>
    // create(Object::class) returns a list<Object>
    private static function getCreateManyReturnType(FunctionReturnTypeProviderEvent $event): ?Union
    {
        $targetClass = PsalmTypeHelper::resolveFactoryTargetClass($event->getCallArgs()[1] ?? null);

        $createdObjectType = match (PsalmTypeHelper::isFactoryTargetClassPersisted($targetClass)) {
            true => PsalmTypeHelper::genericType(Proxy::class, $targetClass),
            false => PsalmTypeHelper::classType($targetClass),
            null => null
        };

        if (!$createdObjectType) {
            return null;
        }

        return new \Psalm\Type\Union([new \Psalm\Type\Atomic\TKeyedArray([$createdObjectType], is_list: true)]);
    }
}
