<?php

use Symfony\Component\Filesystem\Filesystem;
use Zenstruck\Foundry\Test\TestState;
use Zenstruck\Foundry\Tests\Fixtures\Stories\TagStory;

require \dirname(__DIR__).'/vendor/autoload.php';

(new Filesystem())->remove(__DIR__.'/../var');

TestState::disableDefaultProxyAutoRefresh();

if (\getenv('DATABASE_URL')) {
    TestState::addGlobalState(static function() {
        TagStory::load();
    });
}
