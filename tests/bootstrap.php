<?php

use Symfony\Component\Filesystem\Filesystem;
use Zenstruck\Foundry\Test\TestState;
use Zenstruck\Foundry\Tests\Fixtures\Stories\TagStory;

require \dirname(__DIR__).'/vendor/autoload.php';

(new Filesystem())->remove(\sys_get_temp_dir().'/zenstruck-foundry');

if (!\getenv('USE_FOUNDRY_BUNDLE')) {
    TestState::withoutBundle();
}

TestState::addGlobalState(static function() {
    TagStory::load();
});
