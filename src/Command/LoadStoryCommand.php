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

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Zenstruck\Foundry\Story;

/**
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 */
final class LoadStoryCommand extends Command
{
    public function __construct(
        /** @var ServiceLocator<Story> */
        private readonly ServiceLocator $stories,
        /** @var ServiceLocator<list<Story>> */
        private readonly ServiceLocator $groupedStories,
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('name', InputArgument::OPTIONAL, 'The name of the story to load');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $stories = [];

        if (null === ($name = $input->getArgument('name'))) {
            // todo: ask interactively
        }

        if ($this->stories->has($name)) {
            $stories = [$this->stories->get($name)];
        }

        if ($this->groupedStories->has($name)) {
            $stories = $this->groupedStories->get($name);
        }

        if (!$stories) {
            throw new InvalidArgumentException("Fixture with name \"$name\" does not exist.");
        }

        foreach ($stories as $story) {
            // todo add some output
            $story::load();
        }

        return self::SUCCESS;
    }
}
