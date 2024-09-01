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
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\ErrorHandler\ErrorHandler;
use Symfony\Component\Filesystem\Filesystem;
use Zenstruck\Foundry\Tests\Fixture\MigrationTests\TestMigrationKernel;

require \dirname(__DIR__).'/vendor/autoload.php';

$fs = new Filesystem();

$fs->remove(__DIR__.'/../var');

(new Dotenv())->usePutenv()->loadEnv(__DIR__.'/../.env');

$fs->remove(__DIR__.'/Fixture/MigrationTests/Migrations');
$fs->mkdir(__DIR__.'/Fixture/MigrationTests/Migrations');

$kernel = new TestMigrationKernel('test', true);
$kernel->boot();

$application = new Application($kernel);
$application->setAutoExit(false);

runCommand($application, 'doctrine:database:drop --if-exists --force', canFail: true);
runCommand($application, 'doctrine:database:create', canFail: true);

$configuration = getenv('WITH_MIGRATION_CONFIGURATION_FILE') ? '--configuration '.getcwd().'/'.getenv('WITH_MIGRATION_CONFIGURATION_FILE') : '';
runCommand($application, "doctrine:migrations:diff {$configuration}");
runCommand($application, 'doctrine:database:drop --force', canFail: true);

$kernel->shutdown();

\set_exception_handler([new ErrorHandler(), 'handleException']);

function runCommand(Application $application, string $command, bool $canFail = false): void
{
    $exit = $application->run(
        new StringInput($command),
        $output = new BufferedOutput()
    );

    if (0 !== $exit && !$canFail) {
        throw new \RuntimeException(\sprintf('Error running "%s": %s', "$command -v", $output->fetch()));
    }
}
