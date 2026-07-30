<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Command;

use DAMA\DoctrineTestBundle\Doctrine\DBAL\StaticDriver;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\LogicException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpKernel\KernelInterface;
use Zenstruck\Foundry\Persistence\ResetDatabase\BeforeFirstTestResetter;
use Zenstruck\Foundry\Story\FixtureStoryResolver;

/**
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 *
 * @internal
 */
final class LoadFixturesCommand extends Command
{
    public function __construct(
        private readonly FixtureStoryResolver $fixtureStoryResolver,
        /** @var iterable<BeforeFirstTestResetter> */
        private iterable $databaseResetters,
        private KernelInterface $kernel,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('name', InputArgument::OPTIONAL | InputArgument::IS_ARRAY, "Stories' names or stories groups' names to load.")
            ->addOption('append', 'a', InputOption::VALUE_NONE, 'Skip resetting database and append data to the existing database.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!$this->fixtureStoryResolver->hasAnyFixtures()) {
            throw new LogicException('No story as fixture available: add attribute #[AsFixture] to your story classes before running this command.');
        }

        $io = new SymfonyStyle($input, $output);

        if (!$input->getOption('append')) {
            if (!$io->confirm('The database will be recreated! Do you want to continue?')) {
                $io->warning('Aborting command execution. Use the --append option to skip database reset.');

                return self::SUCCESS;
            }

            $this->resetDatabase();
        }

        /** @var array<string>|string|null $fixtureNamesOrGroups */
        $fixtureNamesOrGroups = $input->getArgument('name');

        if (\is_string($fixtureNamesOrGroups)) {
            trigger_deprecation('zenstruck/foundry', '2.12', 'Passing a string as the "name" argument of the "foundry:load-fixtures" command is deprecated and will throw an error in Foundry 3: pass an array of names instead.');

            $fixtureNamesOrGroups = [$fixtureNamesOrGroups];
        }

        if (!$fixtureNamesOrGroups) {
            $fixtureNamesOrGroups = [$this->getNameWhenNotProvided($io)];
        }

        $resolvedStories = [];
        foreach (\array_unique($fixtureNamesOrGroups) as $fixtureNameOrGroup) {
            $resolvedStories[$fixtureNameOrGroup] = $this->fixtureStoryResolver->resolve($fixtureNameOrGroup);
        }

        $loadedStoryClasses = [];
        foreach ($resolvedStories as $fixtureNameOrGroup => $stories) {
            $fixtureNameOrGroup = (string) $fixtureNameOrGroup;

            if ($this->fixtureStoryResolver->hasFixture($fixtureNameOrGroup)) {
                $io->comment("Loading story with name \"{$fixtureNameOrGroup}\"...");
            } else {
                $io->comment("Loading stories group \"{$fixtureNameOrGroup}\"...");
            }

            foreach ($stories as $name => $storyClass) {
                if (isset($loadedStoryClasses[$storyClass])) {
                    $io->warning("Story \"{$storyClass}\" (name: {$name}) already loaded. Skipping...");

                    continue;
                }

                $storyClass::load();
                $loadedStoryClasses[$storyClass] = true;

                if ($io->isVerbose()) {
                    $io->info("Story \"{$storyClass}\" loaded (name: {$name}).");
                }
            }
        }

        $io->success('Stories successfully loaded!');

        return self::SUCCESS;
    }

    private function resetDatabase(): void
    {
        // it is very not likely that we need dama when running this command
        if (\class_exists(StaticDriver::class) && StaticDriver::isKeepStaticConnections()) {
            StaticDriver::setKeepStaticConnections(false);
        }

        foreach ($this->databaseResetters as $databaseResetter) {
            $databaseResetter->resetBeforeFirstTest($this->kernel);
        }
    }

    private function getNameWhenNotProvided(SymfonyStyle $io): string
    {
        if ($this->fixtureStoryResolver->hasOnlyOneFixture()) {
            return $this->fixtureStoryResolver->availableFixtureNames()[0];
        }

        $storyNames = $this->fixtureStoryResolver->availableFixtureNames();
        if (\count($this->fixtureStoryResolver->availableGroupNames()) > 0) {
            $storyNames[] = '(choose a group of stories...)';
        }
        $name = $io->choice('Choose a story to load:', $storyNames);

        if (!$this->fixtureStoryResolver->hasFixture($name)) {
            $groupsNames = $this->fixtureStoryResolver->availableGroupNames();
            $name = $io->choice('Choose a group of stories:', $groupsNames);
        }

        return $name;
    }
}
