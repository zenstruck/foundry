<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Maker;

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
use Symfony\Component\Console\Question\Question;
use Symfony\Component\HttpKernel\KernelInterface;
use Zenstruck\Foundry\Maker\Factory\FactoryCandidatesClassesExtractor;
use Zenstruck\Foundry\Maker\Factory\FactoryGenerator;
use Zenstruck\Foundry\Maker\Factory\MakeFactoryQuery;
use Zenstruck\Foundry\Maker\Factory\NoPersistenceObjectsAutoCompleter;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class MakeFactory extends AbstractMaker
{
    private const GENERATE_ALL_FACTORIES = 'All';

    public function __construct(
        private KernelInterface $kernel,
        private FactoryGenerator $factoryGenerator,
        private NoPersistenceObjectsAutoCompleter $noPersistenceObjectsAutoCompleter,
        private FactoryCandidatesClassesExtractor $factoryCandidatesClassesExtractor,
        private string $defaultNamespace,
    ) {
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
            ->addOption('namespace', null, InputOption::VALUE_REQUIRED, 'Customize the namespace for generated factories')
            ->addOption('test', null, InputOption::VALUE_NONE, 'Create in <fg=yellow>tests/</> instead of <fg=yellow>src/</>')
            ->addOption('all-fields', null, InputOption::VALUE_NONE, 'Create defaults for all entity fields, not only required fields')
            ->addOption('no-persistence', null, InputOption::VALUE_NONE, 'Create a factory for an object not managed by Doctrine')
            ->addOption('with-phpdoc', null, InputOption::VALUE_NONE, 'Adds @method and @phpstan-method to the Factory (can help with autocompletion in some cases)')
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

        if (!$input->getOption('test')) {
            $io->text('// Note: pass <fg=yellow>--test</> if you want to generate factories in your <fg=yellow>tests/</> directory');
            $io->newLine();
        }

        if (!$input->getOption('all-fields')) {
            $io->text('// Note: pass <fg=yellow>--all-fields</> if you want to generate default values for all fields, not only required fields');
            $io->newLine();
        }

        if ($input->getArgument('class')) {
            return;
        }

        if ($input->getOption('no-persistence')) {
            $question = new Question('Not persisted fully qualified class name to create a factory for');
            $question->setValidator(
                static function(string $class) {
                    if (!\class_exists($class)) {
                        throw new RuntimeCommandException("Given class \"{$class}\" does not exist.");
                    }

                    return $class;
                },
            );
            $question->setAutocompleterValues($this->noPersistenceObjectsAutoCompleter->getAutocompleteValues());
            $class = $io->askQuestion($question);
        } else {
            $argument = $command->getDefinition()->getArgument('class');

            $class = $io->choice($argument->getDescription(), \array_merge($this->factoryCandidatesClassesExtractor->factoryCandidatesClasses(), [self::GENERATE_ALL_FACTORIES]));
        }

        $input->setArgument('class', $class);
    }

    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator): void
    {
        $class = $input->getArgument('class');
        $generateAllFactories = self::GENERATE_ALL_FACTORIES === $class;
        $classes = $generateAllFactories ? $this->factoryCandidatesClassesExtractor->factoryCandidatesClasses() : [$class];

        foreach ($classes as $class) {
            $this->factoryGenerator->generateFactory(
                $io,
                MakeFactoryQuery::fromInput($input, $class, $generateAllFactories, $generator, $input->getOption('namespace') ?? $this->defaultNamespace));
        }

        $generator->writeChanges();

        $this->writeSuccessMessage($io);

        $io->text([
            'Next: Open your new factory and set default values/states.',
            'Find the documentation at https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#model-factories',
        ]);
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
