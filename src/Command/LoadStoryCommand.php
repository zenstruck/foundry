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
        private readonly ServiceLocator $stories
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
        if ($name = $input->getArgument('name')) {
            if (!$this->stories->has($name)) {
                throw new InvalidArgumentException("Fixture with name \"$name\" does not exist.");
            }

            $this->stories->get($name)->build();
        }

        return self::SUCCESS;
    }
}
