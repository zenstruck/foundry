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

use Zenstruck\Foundry\Attribute\FactoryShortName;
use Zenstruck\Foundry\Factory;
use Zenstruck\Foundry\ObjectFactory;

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
        }
    }

    /**
     * @return ObjectFactory<object>
     *
     * @throws FactoryNotResolvableException
     */
    public function factoryFor(string $shortName): ObjectFactory
    {
        $normalized = \strtolower((string)\preg_replace('/[\s_-]+/', '', $shortName));

        if (!isset($this->factoryMap[$normalized])) {
            throw FactoryNotResolvableException::forName($shortName);
        }

        $factories = $this->factoryMap[$normalized];

        if (\count($factories) > 1) {
            throw FactoryNotResolvableException::conflict($shortName, array_map(static fn(ObjectFactory $f) => $f::class, $factories));
        }

        return $factories[0];
    }

    /**
     * @param class-string<ObjectFactory<object>> $factoryClass
     */
    private function shortNameFor(string $factoryClass): string
    {
        $reflection = new \ReflectionClass($factoryClass);

        // Check for #[FactoryShortName] attribute
        $attributes = $reflection->getAttributes(FactoryShortName::class);

        if ([] !== $attributes) {
            return \strtolower($attributes[0]->newInstance()->name);
        }

        // Auto-generate from class name
        $shortClass = $reflection->getShortName();

        if (\str_ends_with($shortClass, 'Factory')) {
            $shortClass = \substr($shortClass, 0, -7);
        }

        return \strtolower($shortClass);
    }
}
