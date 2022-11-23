<?php

declare(strict_types=1);

namespace Zenstruck\Foundry\Bundle\Maker;

use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;

final class MakeFactoryData
{
    /** @var list<string> */
    private array $uses;
    /** @var array<string, string> */
    private array $defaultProperties = [];

    public function __construct(private \ReflectionClass $object, private ?\ReflectionClass $repository)
    {
        $this->uses = [
            ModelFactory::class,
            Proxy::class,
            $object->getName(),
        ];

        if ($repository) {
            $this->uses[] = $repository->getName();
        }
    }

    public function getObject(): \ReflectionClass
    {
        return $this->object;
    }

    public function getObjectShortName(): string
    {
        return $this->object->getShortName();
    }

    /** @return class-string */
    public function getObjectFullyQualifiedClassName(): string
    {
        return $this->object->getName();
    }

    public function getRepositoryShortName(): ?string
    {
        return $this->repository?->getShortName();
    }

    public function addUse(string $use): void
    {
        if (!\in_array($use, $this->uses, true)) {
            $this->uses[] = $use;
        }
    }

    public function renderUses(): string
    {
        $uses = $this->uses;
        \sort($uses);

        return \implode(
            "\n",
            \array_map(
                static fn(string $use): string => "use {$use};",
                $uses
            )
        )."\n";
    }

    public function addDefaultProperty(string $propertyName, string $defaultValue): void
    {
        $this->defaultProperties[$propertyName] = $defaultValue;
    }

    public function renderDefaultProperties(): string
    {
        $defaultProperties = $this->defaultProperties;
        \ksort($defaultProperties);

        $indents = \str_repeat(' ', 12);

        return \implode(
            "\n",
            \array_map(
                static fn(string $name, string $value): string => "{$indents}'{$name}' => {$value}",
                \array_keys($defaultProperties),
                $defaultProperties,
            )
        )."\n";
    }
}
