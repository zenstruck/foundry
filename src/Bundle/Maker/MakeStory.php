<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Bundle\Maker;

use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\DependencyBuilder;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\InputConfiguration;
use Symfony\Bundle\MakerBundle\Maker\AbstractMaker;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class MakeStory extends AbstractMaker
{
    public static function getCommandName(): string
    {
        return 'make:story';
    }

    public static function getCommandDescription(): string
    {
        return 'Creates a Foundry story';
    }

    public function configureCommand(Command $command, InputConfiguration $inputConfig): void
    {
        $command
            ->setDescription(self::getCommandDescription())
            ->addArgument('name', InputArgument::OPTIONAL, 'The name of the story class (e.g. <fg=yellow>DefaultCategoriesStory</>)')
            ->addOption('test', null, InputOption::VALUE_NONE, 'Create in <fg=yellow>tests/</> instead of <fg=yellow>src/</>')
        ;

        $inputConfig->setArgumentAsNonInteractive('name');
    }

    public function interact(InputInterface $input, ConsoleStyle $io, Command $command): void
    {
        if ($input->getArgument('name')) {
            return;
        }

        if (!$input->getOption('test')) {
            $io->text('// Note: pass <fg=yellow>--test</> if you want to generate stories in your <fg=yellow>tests/</> directory');
            $io->newLine();
        }

        $argument = $command->getDefinition()->getArgument('name');
        $value = $io->ask($argument->getDescription(), null, static fn(?string $value = null): string => \Symfony\Bundle\MakerBundle\Validator::notBlank($value));
        $input->setArgument($argument->getName(), $value);
    }

    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator): void
    {
        $storyClassNameDetails = $generator->createClassNameDetails(
            $input->getArgument('name'),
            $input->getOption('test') ? 'Tests\\Story' : 'Story',
            'Story'
        );

        $generator->generateClass(
            $storyClassNameDetails->getFullName(),
            __DIR__.'/../Resources/skeleton/Story.tpl.php',
            []
        );

        $generator->writeChanges();

        $this->writeSuccessMessage($io);

        $io->text([
            'Next: Open your story class and start customizing it.',
            'Find the documentation at https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#stories',
        ]);
    }

    public function configureDependencies(DependencyBuilder $dependencies): void
    {
        // noop
    }
}
