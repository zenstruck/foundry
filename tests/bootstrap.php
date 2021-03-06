<?php

use Symfony\Component\Filesystem\Filesystem;
use Zenstruck\Foundry\Test\TestState;
use Zenstruck\Foundry\Tests\Fixtures\Stories\TagStory;

require \dirname(__DIR__).'/vendor/autoload.php';

(new Filesystem())->remove(__DIR__.'/../var');

TestState::disableDefaultProxyAutoRefresh();
TestState::addGlobalState(static function() {
    TagStory::load();
});
