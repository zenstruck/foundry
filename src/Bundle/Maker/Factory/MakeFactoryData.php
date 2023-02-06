<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Bundle\Maker\Factory;

use Symfony\Bundle\MakerBundle\Str;
use Symfony\Bundle\MakerBundle\Util\ClassNameDetails;
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;
use Zenstruck\Foundry\RepositoryProxy;

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
    /** @var non-empty-list<MakeFactoryPHPDocMethod> */
    private array $methodsInPHPDoc;

    public function __construct(private \ReflectionClass $object, private ClassNameDetails $factoryClassNameDetails, private ?\ReflectionClass $repository, private string $staticAnalysisTool, private bool $persisted)
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

    public function getFactoryClassNameDetails(): ClassNameDetails
    {
        return $this->factoryClassNameDetails;
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

    public function hasStaticAnalysisTool(): bool
    {
        return self::STATIC_ANALYSIS_TOOL_NONE !== $this->staticAnalysisTool;
    }

    public function staticAnalysisTool(): string
    {
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
            "self::faker()->randomElement({$enumShortClassName}::cases()),"
        );
    }
}
