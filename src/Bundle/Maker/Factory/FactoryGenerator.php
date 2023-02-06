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
use Symfony\Bundle\MakerBundle\Str;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpKernel\KernelInterface;
use Zenstruck\Foundry\Bundle\Maker\Factory\Exception\FactoryClassAlreadyExistException;

/**
 * @internal
 */
final class FactoryGenerator
{
    public const PHPSTAN_PATH = '/vendor/phpstan/phpstan/phpstan';
    public const PSALM_PATH = '/vendor/vimeo/psalm/psalm';

    /** @param \Traversable<int, DefaultPropertiesGuesser> $defaultPropertiesGuessers */
    public function __construct(
        private ManagerRegistry $managerRegistry,
        private KernelInterface $kernel,
        private \Traversable $defaultPropertiesGuessers,
        private FactoryClassMap $factoryClassMap,
        private NamespaceGuesser $namespaceGuesser,
    ) {
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
            try {
                $this->factoryClassMap->addFactoryForClass($factoryClass, $class);
            } catch (FactoryClassAlreadyExistException) {
                $question = new Question(
                    \sprintf(
                        'Class "%s" already exists. Chose another one please (it will be generated in namespace "%s")',
                        Str::getShortClassName($factoryClass),
                        Str::getNamespace($factoryClass)
                    )
                );

                $question->setValidator(
                    function(string $newClassName) use ($factoryClass) {
                        $newFactoryClass = \sprintf('%s\\%s', Str::getNamespace($factoryClass), $newClassName);
                        if ($this->factoryClassMap->factoryClassExists($newFactoryClass)) {
                            throw new RuntimeCommandException("Class \"{$newFactoryClass}\" also already exists!");
                        }

                        return $newFactoryClass;
                    }
                );
                $factoryClass = $io->askQuestion($question);

                $this->factoryClassMap->addFactoryForClass($factoryClass, $class);
            }
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
            ($this->namespaceGuesser)($generator, $class, $makeFactoryQuery->getNamespace(), $makeFactoryQuery->isTest()),
            'Factory'
        );

        if ($persisted = $makeFactoryQuery->isPersisted()) {
            /** @var ODMClassMetadata|ORMClassMetadata|null $metadata */
            $metadata = $this->managerRegistry->getManagerForClass($class)?->getClassMetadata($class);

            // Doctrine ORM will not return a metadata for embedded classes but Doctrine ODM will.
            // We have to remove persisting for both cases.
            if (!$metadata || $metadata instanceof ODMClassMetadata && $metadata->isEmbeddedDocument) {
                $persisted = false;
            }

            if ($persisted) {
                $repository = new \ReflectionClass($this->managerRegistry->getRepository($object->getName()));
            }
        }

        return new MakeFactoryData(
            $object,
            $factory,
            $repository ?? null,
            $this->staticAnalysisTool(),
            $persisted
        );
    }

    private function staticAnalysisTool(): string
    {
        return match (true) {
            \file_exists($this->kernel->getProjectDir().self::PHPSTAN_PATH) => MakeFactoryData::STATIC_ANALYSIS_TOOL_PHPSTAN,
            \file_exists($this->kernel->getProjectDir().self::PSALM_PATH) => MakeFactoryData::STATIC_ANALYSIS_TOOL_PSALM,
            default => MakeFactoryData::STATIC_ANALYSIS_TOOL_NONE,
        };
    }
}
