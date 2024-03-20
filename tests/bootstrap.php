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
use Zenstruck\Foundry\Tests\Fixtures\Kernel;

require \dirname(__DIR__).'/vendor/autoload.php';

(new Dotenv())->usePutenv()->loadEnv(__DIR__.'/../.env');

$fs = new Filesystem();

$fs->remove(__DIR__.'/../var');

if (\getenv('DATABASE_URL') && \getenv('TEST_MIGRATIONS')) {
    $fs->remove(__DIR__.'/Fixtures/Migrations');
    $fs->mkdir(__DIR__.'/Fixtures/Migrations');

    $kernel = new Kernel('test', true);
    $kernel->boot();

    $application = new Application($kernel);
    $application->setAutoExit(false);

    if (!\str_starts_with(\getenv('DATABASE_URL'), 'sqlite')) {
        $application->run(new StringInput('doctrine:database:create --if-not-exists --no-interaction'), new NullOutput());
    }

    $application->run(new StringInput('doctrine:schema:drop --force --no-interaction'), new NullOutput());
    $application->run(new StringInput('doctrine:migrations:diff --no-interaction'), new NullOutput());

    $kernel->shutdown();
}
