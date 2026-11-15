<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Tally\Configuration;

// Tally lives in bin/tools/tally and only loads its own autoloader: load the project's in this
// process, which discovers the benchmarks, and in the processes that run them
require_once __DIR__.'/vendor/autoload.php';

return Configuration::create()
    ->withPaths([__DIR__.'/tests/Benchmark'])
    ->withIniEntries(['auto_prepend_file='.__DIR__.'/vendor/autoload.php', 'memory_limit=-1'])
    ->withEnvironmentVariables([
        'PATH', 'HOME', 'TMPDIR', 'LANG', 'LC_ALL', 'PHPRC', 'PHP_INI_SCAN_DIR', // Tally's defaults
        'DATABASE_URL', 'MONGO_URL', 'USE_DAMA_DOCTRINE_TEST_BUNDLE', 'USE_PHP_84_LAZY_OBJECTS', 'DATABASE_RESET_MODE',
    ]);
