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
use function Symfony\Component\String\u;

require \dirname(__DIR__) . '/vendor/autoload.php';

$fs = new Filesystem();

$fs->remove(__DIR__ . '/../var/cache');

if (!isset($_ENV['PARATEST'])) {
    (new Dotenv())->usePutenv()->loadEnv(__DIR__ . '/../.env', testEnvs: []);
}

$databaseUrl = u($_ENV['DATABASE_URL'] ?? '');
if ($databaseUrl->startsWith('sqlite:')
    && $fs->exists(
        $sqliteFile = $databaseUrl->after('sqlite://')->trimStart('/')->replace('%kernel.project_dir%', dirname(__DIR__))->toString()
    )
) {
    $fs->remove($sqliteFile);
}

$command = \implode(' ', $_SERVER['argv']);

if (\str_contains($command, '--testsuite reset-database')) {
    require __DIR__ . '/bootstrap-reset-database.php';
}
