<?php

use Symfony\Component\Filesystem\Filesystem;
use Zenstruck\Foundry\Test\TestState;

require \dirname(__DIR__).'/vendor/autoload.php';

(new Filesystem())->remove(__DIR__.'/../var');

TestState::disableDefaultProxyAutoRefresh();
