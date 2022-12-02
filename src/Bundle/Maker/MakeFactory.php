<?php

namespace Zenstruck\Foundry\Bundle\Maker;

use Doctrine\Persistence\ManagerRegistry;
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
use Zenstruck\Foundry\Bundle\Maker\Factory\DefaultPropertiesGuesser;
use Zenstruck\Foundry\Bundle\Maker\Factory\FactoryFinder;
use Zenstruck\Foundry\Bundle\Maker\Factory\MakeFactoryData;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class MakeFactory extends AbstractMaker
{
    /** @param \Traversable<int, DefaultPropertiesGuesser> $defaultPropertiesGuessers */
    public function __construct(private ManagerRegistry $managerRegistry, private FactoryFinder $factoryFinder, private KernelInterface $kernel, private \Traversable $defaultPropertiesGuessers)
    {
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
            ->addOption('no-persistence', null, InputOption::VALUE_NONE, 'Create a factory for an object not managed by Doctrine')
        ;

        $inputConfig->setArgumentAsNonInteractive('class');
    }

    public function interact(InputInterface $input, ConsoleStyle $io, Command $command): void
    {
        if (!$this->doctrineEnabled() && !$input->getOption('no-persistence')) {
            $io->text('// Note: Doctrine not enabled: auto-activating <fg=yellow>--no-persistence</> option.');
            $io->newLine();

            $input->setOption('no-persistence', true);
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

        if ($input->getOption('no-persistence')) {
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

        $makeFactoryData = $this->createMakeFactoryData($class, !$input->getOption('no-persistence'));

        $factory = $generator->createClassNameDetails(
            $makeFactoryData->getObjectShortName(),
            $this->guessNamespace($generator, $input->getOption('namespace'), (bool) $input->getOption('test')),
            'Factory'
        );

        foreach ($this->defaultPropertiesGuessers as $defaultPropertiesGuesser) {
            if ($defaultPropertiesGuesser->supports($makeFactoryData)) {
                $defaultPropertiesGuesser($makeFactoryData, $input->getOption('all-fields'));
            }
        }

        $generator->generateClass(
            $factory->getFullName(),
            __DIR__.'/../Resources/skeleton/Factory.tpl.php',
            [
                'makeFactoryData' => $makeFactoryData,
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

                if (!$this->factoryFinder->classHasFactory($metadata->getName())) {
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

    private function phpstanEnabled(): bool
    {
        return \file_exists("{$this->kernel->getProjectDir()}/vendor/phpstan/phpstan/phpstan");
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

    /**
     * @param class-string $class
     */
    private function createMakeFactoryData(string $class, bool $persisted): MakeFactoryData
    {
        $object = new \ReflectionClass($class);

        if ($persisted) {
            $repository = new \ReflectionClass($this->managerRegistry->getRepository($object->getName()));

            if (\str_starts_with($repository->getName(), 'Doctrine')) {
                // not using a custom repository
                $repository = null;
            }
        }

        return new MakeFactoryData($object, $repository ?? null, $this->phpstanEnabled(), $persisted);
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
}
