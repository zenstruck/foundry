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

namespace Zenstruck\Foundry\Test\Behat;

use Symfony\Component\String\Inflector\EnglishInflector;
use Zenstruck\Foundry\Attribute\FactoryShortName;
use Zenstruck\Foundry\Factory;
use Zenstruck\Foundry\ObjectFactory;
use Zenstruck\Foundry\Test\Behat\Exception\FactoryNotResolvable;

use function Symfony\Component\String\u;

/**
 * @internal
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 */
final class FactoryShortNameResolver
{
    /**
     * @var array<string, list<ObjectFactory<object>>>
     */
    private array $factoryMap = [];

    /**
     * @param iterable<Factory<mixed>> $factories
     */
    public function __construct(iterable $factories)
    {
        $inflector = new EnglishInflector();

        foreach ($factories as $factory) {
            if (!$factory instanceof ObjectFactory) {
                continue;
            }

            $shortName = $this->shortNameFor($factory::class);

            // we allow multiple factories to have the same shortName:
            // we'll only trigger an error when trying to access an unambiguous shortname
            // we don't want to force the user to resolve all potential conflicts at startup.
            $this->factoryMap[$shortName] ??= [];
            $this->factoryMap[$shortName][] = $factory;

            $plural = \mb_strtolower($this->factoryShortNameAttribute($factory::class)->pluralName ?? $inflector->pluralize($shortName)[0]);
            $this->factoryMap[$plural] ??= [];
            $this->factoryMap[$plural][] = $factory;
        }
    }

    /**
     * @return ObjectFactory<object>
     *
     * @throws FactoryNotResolvable
     */
    public function factoryFor(string $shortName): ObjectFactory
    {
        $normalized = \mb_strtolower($shortName);

        if (!isset($this->factoryMap[$normalized])) {
            throw FactoryNotResolvable::forName($shortName);
        }

        $factories = $this->factoryMap[$normalized];

        if (\count($factories) > 1) {
            throw FactoryNotResolvable::conflict($shortName, \array_map(static fn(ObjectFactory $f) => $f::class, $factories));
        }

        return $factories[0]::new();
    }

    /**
     * @return class-string
     */
    public function targetObjectClassFor(string $shortName): string
    {
        return $this->factoryFor($shortName)::class();
    }

    /**
     * @param class-string $className
     */
    public function hasFactoryForClass(string $className): bool
    {
        return array_any(
            $this->factoryMap,
            static fn(array $factories) => array_any(
                $factories,
                static fn(ObjectFactory $factory) => $factory::class() === $className,
            )
        );
    }

    /**
     * @param class-string $className
     */
    public function getShortNameForClass(string $className): string
    {
        return array_find_key( // @phpstan-ignore return.type (PHPStan bug)
            $this->factoryMap,
            static fn(array $factories) => array_any(
                $factories,
                static fn(ObjectFactory $factory) => $factory::class() === $className,
            )
        );
    }

    /**
     * @param class-string<ObjectFactory<object>> $factoryClass
     */
    private function shortNameFor(string $factoryClass): string
    {
        $attribute = $this->factoryShortNameAttribute($factoryClass);

        if ($attribute) {
            return \mb_strtolower($attribute->shortName);
        }

        $shortClass = u((new \ReflectionClass($factoryClass))->getShortName());

        if ($shortClass->endsWith('Factory')) {
            $shortClass = $shortClass->slice(0, -7);
        }

        return $shortClass
            ->snake()
            ->replace('_', ' ')
            ->lower()
            ->toString();
    }

    /**
     * @param class-string<ObjectFactory<object>> $factoryClass
     */
    private function factoryShortNameAttribute(string $factoryClass): ?FactoryShortName
    {
        $reflection = new \ReflectionClass($factoryClass);

        $attributes = $reflection->getAttributes(FactoryShortName::class);

        return ($attributes[0] ?? null)?->newInstance();
    }
}
