<?php

namespace Zenstruck\Foundry\Bundle\Maker;

use Doctrine\ODM\MongoDB\Mapping\ClassMetadata as ODMClassMetadata;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadataInfo as ORMClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\DependencyBuilder;
use Symfony\Bundle\MakerBundle\Exception\RuntimeCommandException;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\InputConfiguration;
use Symfony\Bundle\MakerBundle\Maker\AbstractMaker;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\HttpKernel\KernelInterface;
use Zenstruck\Foundry\ModelFactory;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class MakeFactory extends AbstractMaker
{
    private const DEFAULTS_FOR_PERSISTED = [
        'ARRAY' => '[],',
        'ASCII_STRING' => 'self::faker()->text({length}),',
        'BIGINT' => 'self::faker()->randomNumber(),',
        'BLOB' => 'self::faker()->text(),',
        'BOOLEAN' => 'self::faker()->boolean(),',
        'DATE' => 'self::faker()->dateTime(),',
        'DATE_MUTABLE' => 'self::faker()->dateTime(),',
        'DATE_IMMUTABLE' => '\DateTimeImmutable::createFromMutable(self::faker()->dateTime()),',
        'DATETIME_MUTABLE' => 'self::faker()->dateTime(),',
        'DATETIME_IMMUTABLE' => '\DateTimeImmutable::createFromMutable(self::faker()->dateTime()),',
        'DATETIMETZ_MUTABLE' => 'self::faker()->dateTime(),',
        'DATETIMETZ_IMMUTABLE' => '\DateTimeImmutable::createFromMutable(self::faker()->dateTime()),',
        'DECIMAL' => 'self::faker()->randomFloat(),',
        'FLOAT' => 'self::faker()->randomFloat(),',
        'INTEGER' => 'self::faker()->randomNumber(),',
        'JSON' => '[],',
        'JSON_ARRAY' => '[],',
        'SIMPLE_ARRAY' => '[],',
        'SMALLINT' => 'self::faker()->numberBetween(1, 32767),',
        'STRING' => 'self::faker()->text({length}),',
        'TEXT' => 'self::faker()->text({length}),',
        'TIME_MUTABLE' => 'self::faker()->datetime(),',
        'TIME_IMMUTABLE' => '\DateTimeImmutable::createFromMutable(self::faker()->datetime()),',
    ];

    private const DEFAULTS_FOR_NOT_PERSISTED = [
        'array' => '[],',
        'string' => 'self::faker()->sentence(),',
        'int' => 'self::faker()->randomNumber(),',
        'float' => 'self::faker()->randomFloat(),',
        'bool' => 'self::faker()->boolean(),',
        \DateTime::class => 'self::faker()->dateTime(),',
        \DateTimeImmutable::class => '\DateTimeImmutable::createFromMutable(self::faker()->dateTime()),',
    ];

    /** @var string[] */
    private array $entitiesWithFactories;

    public function __construct(private ManagerRegistry $managerRegistry, \Traversable $factories, private string $projectDir, private KernelInterface $kernel)
    {
        $this->entitiesWithFactories = \array_map(
            static fn(ModelFactory $factory): string => $factory::getEntityClass(),
            \iterator_to_array($factories)
        );
    }

    public static function getCommandName(): string
    {
        return 'make:factory';
    }

    public static function getCommandDescription(): string
    {
        return 'Creates a Foundry model factory for a Doctrine entity class or a regular object';
    }

    public function configureDependencies(DependencyBuilder $dependencies): void
    {
        // noop
    }

    public function configureCommand(Command $command, InputConfiguration $inputConfig): void
    {
        $command
            ->setDescription(self::getCommandDescription())
            ->addArgument('class', InputArgument::OPTIONAL, 'Entity, Document or class to create a factory for')
            ->addOption('namespace', null, InputOption::VALUE_REQUIRED, 'Customize the namespace for generated factories', 'Factory')
            ->addOption('test', null, InputOption::VALUE_NONE, 'Create in <fg=yellow>tests/</> instead of <fg=yellow>src/</>')
            ->addOption('all-fields', null, InputOption::VALUE_NONE, 'Create defaults for all entity fields, not only required fields')
            ->addOption('not-persisted', null, InputOption::VALUE_NONE, 'Create a factory for an object not managed by Doctrine')
        ;

        $inputConfig->setArgumentAsNonInteractive('class');
    }

    public function interact(InputInterface $input, ConsoleStyle $io, Command $command): void
    {
        if (!$this->doctrineEnabled() && !$input->getOption('not-persisted')) {
            $io->text('// Note: Doctrine not enabled: auto-activating <fg=yellow>--not-persisted</> option.');
            $io->newLine();

            $input->setOption('not-persisted', true);
        }

        if ($input->getArgument('class')) {
            return;
        }

        if (!$input->getOption('test')) {
            $io->text('// Note: pass <fg=yellow>--test</> if you want to generate factories in your <fg=yellow>tests/</> directory');
            $io->newLine();
        }

        if (!$input->getOption('all-fields')) {
            $io->text('// Note: pass <fg=yellow>--all-fields</> if you want to generate default values for all fields, not only required fields');
            $io->newLine();
        }

        if ($input->getOption('not-persisted')) {
            $class = $io->ask(
                'Not persisted class to create a factory for',
                validator: static function(string $class) {
                    if (!\class_exists($class)) {
                        throw new RuntimeCommandException("Given class \"{$class}\" does not exist.");
                    }

                    return $class;
                }
            );
        } else {
            $argument = $command->getDefinition()->getArgument('class');

            $class = $io->choice($argument->getDescription(), \array_merge($this->entityChoices(), ['All']));
        }

        $input->setArgument('class', $class);
    }

    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator): void
    {
        $class = $input->getArgument('class');
        $classes = 'All' === $class ? $this->entityChoices() : [$class];

        foreach ($classes as $class) {
            $this->generateFactory($class, $input, $io, $generator);
        }
    }

    /**
     * Generates a single entity factory.
     */
    private function generateFactory(string $class, InputInterface $input, ConsoleStyle $io, Generator $generator): void
    {
        if (!\class_exists($class)) {
            $class = $generator->createClassNameDetails($class, 'Entity\\')->getFullName();
        }

        if (!\class_exists($class)) {
            throw new RuntimeCommandException(\sprintf('Class "%s" not found.', $input->getArgument('class')));
        }

        $namespace = $input->getOption('namespace');

        // strip maker's root namespace if set
        if (0 === \mb_strpos($namespace, $generator->getRootNamespace())) {
            $namespace = \mb_substr($namespace, \mb_strlen($generator->getRootNamespace()));
        }

        $namespace = \trim($namespace, '\\');

        // if creating in tests dir, ensure namespace prefixed with Tests\
        if ($input->getOption('test') && 0 !== \mb_strpos($namespace, 'Tests\\')) {
            $namespace = 'Tests\\'.$namespace;
        }

        $object = new \ReflectionClass($class);
        $factory = $generator->createClassNameDetails($object->getShortName(), $namespace, 'Factory');

        if (!$input->getOption('not-persisted')) {
            $repository = new \ReflectionClass($this->managerRegistry->getRepository($object->getName()));

            if (0 !== \mb_strpos($repository->getName(), $generator->getRootNamespace())) {
                // not using a custom repository
                $repository = null;
            }
        }

        $defaultValues = $input->getOption('not-persisted')
            ? $this->defaultPropertiesForNotPersistedObject($object->getName(), $input->getOption('all-fields'))
            : $this->defaultPropertiesForPersistedObject($object->getName(), $input->getOption('all-fields'));
        $defaultValues = \iterator_to_array($defaultValues, true);
        \ksort($defaultValues);

        $generator->generateClass(
            $factory->getFullName(),
            __DIR__.'/../Resources/skeleton/Factory.tpl.php',
            [
                'object' => $object,
                'defaultProperties' => $defaultValues,
                'repository' => $repository ?? null,
                'phpstanEnabled' => $this->phpstanEnabled(),
                'persisted' => !$input->getOption('not-persisted'),
            ]
        );

        $generator->writeChanges();

        $this->writeSuccessMessage($io);

        $io->text([
            'Next: Open your new factory and set default values/states.',
            'Find the documentation at https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#model-factories',
        ]);
    }

    /**
     * @return class-string[]
     */
    private function entityChoices(): array
    {
        $choices = [];

        foreach ($this->managerRegistry->getManagers() as $manager) {
            foreach ($manager->getMetadataFactory()->getAllMetadata() as $metadata) {
                if ($metadata->getReflectionClass()->isAbstract()) {
                    continue;
                }

                if (!\in_array($metadata->getName(), $this->entitiesWithFactories, true)) {
                    $choices[] = $metadata->getName();
                }
            }
        }

        \sort($choices);

        if (empty($choices)) {
            throw new RuntimeCommandException('No entities or documents found, or none left to make factories for.');
        }

        return $choices;
    }

    /**
     * @param class-string $class
     *
     * @return \Generator<string, string>
     */
    private function defaultPropertiesForPersistedObject(string $class, bool $allFields): iterable
    {
        $em = $this->managerRegistry->getManagerForClass($class);

        if (!$em instanceof ObjectManager) {
            return;
        }

        /** @var ORMClassMetadata|ODMClassMetadata $metadata */
        $metadata = $em->getClassMetadata($class);
        $ids = $metadata->getIdentifierFieldNames();

        $dbType = $em instanceof EntityManagerInterface ? 'ORM' : 'ODM';

        foreach ($metadata->fieldMappings as $property) {
            // ignore identifiers and nullable fields
            if ((!$allFields && ($property['nullable'] ?? false)) || \in_array($property['fieldName'], $ids, true)) {
                continue;
            }

            $type = \mb_strtoupper($property['type']);
            $value = "null, // TODO add {$type} {$dbType} type manually";
            $length = $property['length'] ?? '';

            if (\array_key_exists($type, self::DEFAULTS_FOR_PERSISTED)) {
                $value = self::DEFAULTS_FOR_PERSISTED[$type];
            }

            yield $property['fieldName'] => \str_replace('{length}', (string) $length, $value);
        }
    }

    /**
     * @param class-string $class
     *
     * @return \Generator<string, string>
     */
    private function defaultPropertiesForNotPersistedObject(string $class, bool $allFields): \Generator
    {
        $object = new \ReflectionClass($class);

        foreach ($object->getProperties() as $property) {
            // ignore identifiers and nullable fields
            if (!$allFields && ($property->hasDefaultValue() || !$property->hasType() || $property->getType()?->allowsNull())) {
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

            yield $property->getName() => $value;
        }
    }

    private function phpstanEnabled(): bool
    {
        return \file_exists("{$this->projectDir}/vendor/phpstan/phpstan/phpstan");
    }

    private function doctrineEnabled(): bool
    {
        try {
            $this->kernel->getBundle('DoctrineBundle');

            $ormEnabled = true;
        } catch (\InvalidArgumentException) {
            $ormEnabled = false;
        }

        try {
            $this->kernel->getBundle('DoctrineMongoDBBundle');

            $odmEnabled = true;
        } catch (\InvalidArgumentException) {
            $odmEnabled = false;
        }

        return $ormEnabled || $odmEnabled;
    }
}
