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

use Doctrine\ODM\MongoDB\Repository\DocumentRepository;
use Doctrine\ORM\EntityRepository;
use Symfony\Bundle\MakerBundle\Str;
use Symfony\Bundle\MakerBundle\Util\ClassNameDetails;
use Zenstruck\Foundry\ObjectFactory;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;
use Zenstruck\Foundry\Persistence\Proxy;
use Zenstruck\Foundry\Persistence\ProxyRepositoryDecorator;

/**
 * @internal
 */
final class MakeFactoryData
{
    public const STATIC_ANALYSIS_TOOL_NONE = 'none';
    public const STATIC_ANALYSIS_TOOL_PHPSTAN = 'phpstan';
    public const STATIC_ANALYSIS_TOOL_PSALM = 'psalm';

    /** @var list<string> */
    private array $uses;
    /** @var array<string, string> */
    private array $defaultProperties = [];
    /** @var list<MakeFactoryPHPDocMethod> */
    private array $methodsInPHPDoc;

    // @phpstan-ignore-next-line
    public function __construct(
        private \ReflectionClass $object,
        private ClassNameDetails $factoryClassNameDetails,
        private ?\ReflectionClass $repository,
        private string $staticAnalysisTool,
        private bool $persisted,
        bool $withPhpDoc,
    ) {
        $this->uses = [
            $this->getFactoryClass(),
            $object->getName(),
        ];

        if ($this->persisted) {
            $this->uses[] = Proxy::class;
        }

        if ($repository) {
            $this->uses[] = $repository->getName();
            $this->uses[] = ProxyRepositoryDecorator::class;
            if (!\str_starts_with($repository->getName(), 'Doctrine')) {
                $this->uses[] = \is_a($repository->getName(), DocumentRepository::class, allow_string: true) ? DocumentRepository::class : EntityRepository::class;
            }
        }

        $this->methodsInPHPDoc = $withPhpDoc ? MakeFactoryPHPDocMethod::createAll($this) : [];
    }

    // @phpstan-ignore-next-line
    public function getObject(): \ReflectionClass
    {
        return $this->object;
    }

    public function getObjectShortName(): string
    {
        return $this->object->getShortName();
    }

    /**
     * @return class-string<ObjectFactory>
     */
    public function getFactoryClass(): string // @phpstan-ignore-line
    {
        return $this->isPersisted() ? PersistentProxyObjectFactory::class : ObjectFactory::class;
    }

    public function getFactoryClassShortName(): string
    {
        return (new \ReflectionClass($this->getFactoryClass()))->getShortName();
    }

    public function getFactoryClassNameDetails(): ClassNameDetails
    {
        return $this->factoryClassNameDetails;
    }

    /** @return class-string */
    public function getObjectFullyQualifiedClassName(): string
    {
        return $this->object->getName();
    }

    public function getRepositoryReflectionClass(): ?\ReflectionClass // @phpstan-ignore-line
    {
        return $this->repository;
    }

    public function isPersisted(): bool
    {
        return $this->persisted;
    }

    public function staticAnalysisTool(): string
    {
        // if none was detected, let's fallback on phpstan: both psalm and phpstan can read `@phpstan` annotations
        if (self::STATIC_ANALYSIS_TOOL_NONE === $this->staticAnalysisTool) {
            return self::STATIC_ANALYSIS_TOOL_PHPSTAN;
        }

        return $this->staticAnalysisTool;
    }

    /** @param class-string $use */
    public function addUse(string $use): void
    {
        // prevent to add an un-needed "use"
        if (Str::getNamespace($this->factoryClassNameDetails->getFullName()) === Str::getNamespace($use)) {
            return;
        }

        if (!\in_array($use, $this->uses, true)) {
            $this->uses[] = $use;
        }
    }

    /**
     * @return list<string>
     */
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

    /**
     * @return array<string, string>
     */
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
            static fn(MakeFactoryPHPDocMethod $m1, MakeFactoryPHPDocMethod $m2) => $m1->sortValue() <=> $m2->sortValue(),
        );

        return $methodsInPHPDoc;
    }

    public function addEnumDefaultProperty(string $propertyName, string $enumClass): void
    {
        if (\PHP_VERSION_ID < 80100) {
            throw new \LogicException('Cannot add enum for php version inferior than 8.1');
        }

        if (!enum_exists($enumClass)) {
            throw new \InvalidArgumentException("Enum of class \"{$enumClass}\" does not exist.");
        }

        $this->addUse($enumClass);

        $enumShortClassName = Str::getShortClassName($enumClass);
        $this->addDefaultProperty(
            $propertyName,
            "self::faker()->randomElement({$enumShortClassName}::cases()),",
        );
    }
}
