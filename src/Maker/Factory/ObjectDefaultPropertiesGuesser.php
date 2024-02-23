<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Maker\Factory;

use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * @internal
 */
class ObjectDefaultPropertiesGuesser extends AbstractDefaultPropertyGuesser
{
    private const DEFAULTS_FOR_NOT_PERSISTED = [
        'array' => '[],',
        'string' => 'self::faker()->sentence(),',
        'int' => 'self::faker()->randomNumber(),',
        'float' => 'self::faker()->randomFloat(),',
        'bool' => 'self::faker()->boolean(),',
        \DateTime::class => 'self::faker()->dateTime(),',
        \DateTimeImmutable::class => '\DateTimeImmutable::createFromMutable(self::faker()->dateTime()),',
    ];

    public function __invoke(SymfonyStyle $io, MakeFactoryData $makeFactoryData, MakeFactoryQuery $makeFactoryQuery): void
    {
        foreach ($makeFactoryData->getObject()->getProperties() as $property) {
            if (!$this->shouldAddPropertyToFactory($makeFactoryQuery, $property)) {
                continue;
            }

            $type = $this->getPropertyType($property) ?? '';

            $value = \sprintf('null, // TODO add %svalue manually', $type ? "{$type} " : '');

            if (\PHP_VERSION_ID >= 80100 && enum_exists($type)) {
                $makeFactoryData->addEnumDefaultProperty($property->getName(), $type);

                continue;
            }

            if ($type && \class_exists($type) && !\is_a($type, \DateTimeInterface::class, true)) {
                $this->addDefaultValueUsingFactory($io, $makeFactoryData, $makeFactoryQuery, $property->getName(), $type);

                continue;
            }

            if (\array_key_exists($type, self::DEFAULTS_FOR_NOT_PERSISTED)) {
                $value = self::DEFAULTS_FOR_NOT_PERSISTED[$type];
            }

            $makeFactoryData->addDefaultProperty($property->getName(), $value);
        }
    }

    public function supports(MakeFactoryData $makeFactoryData): bool
    {
        return !$makeFactoryData->isPersisted();
    }

    private function shouldAddPropertyToFactory(MakeFactoryQuery $makeFactoryQuery, \ReflectionProperty $property): bool
    {
        // if option "--all-fields" was passed
        if ($makeFactoryQuery->isAllFields()) {
            return true;
        }

        // if property is inside constructor, check if it has a default value
        if ($constructorParameter = $this->getConstructorParameterForProperty($property)) {
            return !$constructorParameter->isDefaultValueAvailable();
        }

        // if the property has a default value, we should not add it to the factory
        if ($property->hasDefaultValue()) {
            return false;
        }

        // if property has type, we need to add it to the factory
        return $property->hasType();
    }

    private function getPropertyType(\ReflectionProperty $property): ?string
    {
        if (!$property->hasType()) {
            $type = $this->getConstructorParameterForProperty($property)?->getType();
        } else {
            $type = $property->getType();
        }

        if (!$type instanceof \ReflectionNamedType) {
            return null;
        }

        return $type->getName();
    }

    private function getConstructorParameterForProperty(\ReflectionProperty $property): ?\ReflectionParameter
    {
        if ($constructor = $property->getDeclaringClass()->getConstructor()) {
            foreach ($constructor->getParameters() as $parameter) {
                if ($parameter->getName() === $property->getName()) {
                    return $parameter;
                }
            }
        }

        return null;
    }
}
