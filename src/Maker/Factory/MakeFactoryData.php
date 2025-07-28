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
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Zenstruck\Foundry\ObjectFactory;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;
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

    private static ?ReflectionExtractor $propertyInfo = null;

    /** @var list<string> */
    private array $uses;
    /** @var array<string, string> */
    private array $defaultProperties = [];
    /** @var list<MakeFactoryPHPDocMethod> */
    private array $methodsInPHPDoc;

    public function __construct(
        private \ReflectionClass $object,
        private ClassNameDetails $factoryClassNameDetails,
        private ?\ReflectionClass $repository,
        private string $staticAnalysisTool,
        private bool $persisted,
        bool $withPhpDoc,
        private bool $forceProperties,
        private bool $addHints,
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
    public function getFactoryClass(): string
    {
        return $this->isPersisted()
            ? (\PHP_VERSION_ID >= 80400 ? PersistentObjectFactory::class : PersistentProxyObjectFactory::class)
            : ObjectFactory::class;
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

    public function getRepositoryReflectionClass(): ?\ReflectionClass
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
        $class = $this->object->getName();

        /**
         * If forceProperties is not set we filter out properties that can not be set because they're either readonly or have no setter.
         * Useful for properties that auto generate when the entity is created and can not be changed like a createdAt property for example.
         *
         * We do this here because we need to get the class of the Entity which only seems to be accessible here.
         */
        $defaultProperties = \array_filter($defaultProperties, function(string $propertyName) use ($class): bool {
            if (true === $this->forceProperties) {
                return true;
            }

            return self::propertyInfo()->isWritable($class, $propertyName) || self::propertyInfo()->isInitializable($class, $propertyName);
        }, \ARRAY_FILTER_USE_KEY);

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

        if (!\enum_exists($enumClass)) {
            throw new \InvalidArgumentException("Enum of class \"{$enumClass}\" does not exist.");
        }

        $this->addUse($enumClass);

        $enumShortClassName = Str::getShortClassName($enumClass);
        $this->addDefaultProperty(
            $propertyName,
            "self::faker()->randomElement({$enumShortClassName}::cases()),",
        );
    }

    public function shouldAddHints(): bool
    {
        return $this->addHints;
    }

    private static function propertyInfo(): ReflectionExtractor
    {
        return self::$propertyInfo ??= new ReflectionExtractor();
    }
}
