<?php

namespace Zenstruck\Foundry\Bundle\Maker\Factory;

use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * @internal
 */
class ObjectDefaultPropertiesGuesser implements DefaultPropertiesGuesser
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
            // ignore identifiers and nullable fields
            if (!$makeFactoryQuery->isAllFields() && ($property->hasDefaultValue() || !$property->hasType() || $property->getType()?->allowsNull())) {
                continue;
            }

            $type = null;
            $reflectionType = $property->getType();
            if ($reflectionType instanceof \ReflectionNamedType) {
                $type = $reflectionType->getName();
            }

            $value = \sprintf('null, // TODO add %svalue manually', $type ? "{$type} " : '');

            if (\array_key_exists($type ?? '', self::DEFAULTS_FOR_NOT_PERSISTED)) {
                $value = self::DEFAULTS_FOR_NOT_PERSISTED[$type];
            }

            $makeFactoryData->addDefaultProperty($property->getName(), $value);
        }
    }

    public function supports(MakeFactoryData $makeFactoryData): bool
    {
        return !$makeFactoryData->isPersisted();
    }
}
