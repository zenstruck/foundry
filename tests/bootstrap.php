<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\Filesystem\Filesystem;
use Zenstruck\Foundry\ORM\ORMPersistenceStrategy;
use Zenstruck\Foundry\Tests\Fixture\TestKernel;

require \dirname(__DIR__).'/vendor/autoload.php';

$fs = new Filesystem();

$fs->remove(__DIR__.'/../var');

(new Dotenv())->usePutenv()->loadEnv(__DIR__.'/../.env');

if (\getenv('DATABASE_URL') && ORMPersistenceStrategy::RESET_MODE_MIGRATE === \getenv('DATABASE_RESET_MODE')) {
    $fs->remove(__DIR__.'/Fixture/Migrations');
    $fs->mkdir(__DIR__.'/Fixture/Migrations');

    $kernel = new TestKernel('test', true);
    $kernel->boot();

    $application = new Application($kernel);
    $application->setAutoExit(false);

    $application->run(new StringInput('doctrine:database:drop --if-exists --force'), new NullOutput());
    $application->run(new StringInput('doctrine:database:create'), new NullOutput());
    $application->run(new StringInput('doctrine:migrations:diff'), new NullOutput());
    $application->run(new StringInput('doctrine:database:drop --force'), new NullOutput());

    $kernel->shutdown();

    $fs->remove(__DIR__.'/../var');
}
