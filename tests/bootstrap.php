<?php

use Symfony\Component\Filesystem\Filesystem;
use Zenstruck\Foundry\Test\GlobalState;
use Zenstruck\Foundry\Tests\Fixtures\Stories\TagStory;

require \dirname(__DIR__).'/vendor/autoload.php';

(new Filesystem())->remove(\sys_get_temp_dir().'/zenstruck-foundry');

GlobalState::add(static function() {
    TagStory::load();
});
