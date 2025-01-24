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

require \dirname(__DIR__).'/vendor/autoload.php';

$fs = new Filesystem();

$fs->remove(__DIR__.'/../var/cache');

(new Dotenv())->usePutenv()->loadEnv(__DIR__.'/../.env');
