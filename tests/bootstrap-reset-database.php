<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\Filesystem\Filesystem;
use Zenstruck\Foundry\Tests\Fixture\ResetDatabase\ResetDatabaseTestKernel;

use function Zenstruck\Foundry\application;
use function Zenstruck\Foundry\runCommand;

require \dirname(__DIR__).'/vendor/autoload.php';

$fs = new Filesystem();

$fs->remove(__DIR__.'/../var/cache');

(new Dotenv())->usePutenv()->loadEnv(__DIR__.'/../.env');

if (ResetDatabaseTestKernel::usesMigrations() && ResetDatabaseTestKernel::shouldGenerateMigrations()) {
    $fs->remove(__DIR__.'/../var/Migrations');
    $fs->mkdir(__DIR__.'/../var/Migrations');

    $kernel = new ResetDatabaseTestKernel('test', true);
    $kernel->boot();

    $application = application($kernel);

    runCommand($application, 'doctrine:database:drop --if-exists --force', canFail: true);
    runCommand($application, 'doctrine:database:create', canFail: true);

    $configurationFiles = ResetDatabaseTestKernel::migrationFiles();

    if (!$configurationFiles) {
        runCommand($application, "doctrine:migrations:diff");
    } else {
        foreach ($configurationFiles as $configurationFile) {
            $configuration = '--configuration '.\getcwd()."/{$configurationFile}";
            runCommand($application, "doctrine:migrations:diff {$configuration}");
        }
    }

    runCommand($application, 'doctrine:database:drop --force', canFail: true);

    $kernel->shutdown();
}
