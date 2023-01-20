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

use Doctrine\ODM\MongoDB\Mapping\ClassMetadata as ODMClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataInfo as ORMClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\MakerBundle\Exception\RuntimeCommandException;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * @internal
 */
final class FactoryGenerator
{
    /** @param \Traversable<int, DefaultPropertiesGuesser> $defaultPropertiesGuessers */
    public function __construct(private ManagerRegistry $managerRegistry, private KernelInterface $kernel, private \Traversable $defaultPropertiesGuessers, private FactoryClassMap $factoryClassMap)
    {
    }

    /**
     * @return class-string The factory's FQCN
     */
    public function generateFactory(SymfonyStyle $io, MakeFactoryQuery $makeFactoryQuery): string
    {
        $class = $makeFactoryQuery->getClass();
        $generator = $makeFactoryQuery->getGenerator();

        if (!\class_exists($class)) {
            $class = $generator->createClassNameDetails($class, 'Entity\\')->getFullName();
        }

        if (!\class_exists($class)) {
            throw new RuntimeCommandException(\sprintf('Class "%s" not found.', $makeFactoryQuery->getClass()));
        }

        $makeFactoryData = $this->createMakeFactoryData($generator, $class, $makeFactoryQuery);

        /** @var class-string $factoryClass */
        $factoryClass = $makeFactoryData->getFactoryClassNameDetails()->getFullName();

        if (!$this->factoryClassMap->classHasFactory($class)) {
            $this->factoryClassMap->addFactoryForClass($factoryClass, $class);
        }

        foreach ($this->defaultPropertiesGuessers as $defaultPropertiesGuesser) {
            if ($defaultPropertiesGuesser->supports($makeFactoryData)) {
                $defaultPropertiesGuesser($io, $makeFactoryData, $makeFactoryQuery);
            }
        }

        $generator->generateClass(
            $factoryClass,
            __DIR__.'/../../Resources/skeleton/Factory.tpl.php',
            [
                'makeFactoryData' => $makeFactoryData,
            ]
        );

        return $factoryClass;
    }

    /** @param class-string $class */
    private function createMakeFactoryData(Generator $generator, string $class, MakeFactoryQuery $makeFactoryQuery): MakeFactoryData
    {
        $object = new \ReflectionClass($class);

        $factory = $generator->createClassNameDetails(
            $object->getShortName(),
            $this->guessNamespace($generator, $makeFactoryQuery->getNamespace(), $makeFactoryQuery->isTest()),
            'Factory'
        );

        if ($persisted = $makeFactoryQuery->isPersisted()) {
            $repository = new \ReflectionClass($this->managerRegistry->getRepository($object->getName()));

            if (\str_starts_with($repository->getName(), 'Doctrine')) {
                // not using a custom repository
                $repository = null;
            }

            /** @var ODMClassMetadata|ORMClassMetadata|null $metadata */
            $metadata = $this->managerRegistry->getManagerForClass($class)?->getClassMetadata($class);

            // Doctrine ORM will not return a metadata for embedded classes but Doctrine ODM will.
            // We have to remove persisting for both cases.
            if (!$metadata || $metadata instanceof ODMClassMetadata && $metadata->isEmbeddedDocument) {
                $persisted = false;
            }
        }

        return new MakeFactoryData(
            $object,
            $factory,
            $repository ?? null,
            $this->phpstanEnabled(),
            $persisted
        );
    }

    private function guessNamespace(Generator $generator, string $namespace, bool $test): string
    {
        // strip maker's root namespace if set
        if (0 === \mb_strpos($namespace, $generator->getRootNamespace())) {
            $namespace = \mb_substr($namespace, \mb_strlen($generator->getRootNamespace()));
        }

        $namespace = \trim($namespace, '\\');

        // if creating in tests dir, ensure namespace prefixed with Tests\
        if ($test && 0 !== \mb_strpos($namespace, 'Tests\\')) {
            $namespace = 'Tests\\'.$namespace;
        }

        return $namespace;
    }

    private function phpstanEnabled(): bool
    {
        return \file_exists("{$this->kernel->getProjectDir()}/vendor/phpstan/phpstan/phpstan");
    }
}
