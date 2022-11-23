<?php

namespace Zenstruck\Foundry\Bundle\Maker;

use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;
use Zenstruck\Foundry\RepositoryProxy;

/**
 * @internal
 */
final class MakeFactoryData
{
    /** @var list<string> */
    private array $uses;
    /** @var array<string, string> */
    private array $defaultProperties = [];
    /** @var non-empty-list<MakeFactoryPHPDocMethod> */
    private array $methodsInPHPDoc;

    public function __construct(private \ReflectionClass $object, private ?\ReflectionClass $repository, private bool $withPHPStanEnabled, private bool $persisted)
    {
        $this->uses = [
            ModelFactory::class,
            Proxy::class,
            $object->getName(),
        ];

        if ($repository) {
            $this->uses[] = $repository->getName();
            $this->uses[] = RepositoryProxy::class;
        }

        $this->methodsInPHPDoc = MakeFactoryPHPDocMethod::createAll($this);
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

    public function isPersisted(): bool
    {
        return $this->persisted;
    }

    public function hasPHPStanEnabled(): bool
    {
        return $this->withPHPStanEnabled;
    }

    public function addUse(string $use): void
    {
        if (!\in_array($use, $this->uses, true)) {
            $this->uses[] = $use;
        }
    }

    public function getUses(): array
    {
        $uses = $this->uses;
        \sort($uses);

        return $uses;
    }

    public function addDefaultProperty(string $propertyName, string $defaultValue): void
    {
        $this->defaultProperties[$propertyName] = $defaultValue;
    }

    public function getDefaultProperties(): array
    {
        $defaultProperties = $this->defaultProperties;
        \ksort($defaultProperties);

        return $defaultProperties;
    }

    /** @return list<MakeFactoryPHPDocMethod> */
    public function getMethodsPHPDoc(): array
    {
        $methodsInPHPDoc = $this->methodsInPHPDoc;
        \usort(
            $methodsInPHPDoc,
            static fn(MakeFactoryPHPDocMethod $m1, MakeFactoryPHPDocMethod $m2) => $m1->sortValue() <=> $m2->sortValue()
        );

        return $methodsInPHPDoc;
    }
}
