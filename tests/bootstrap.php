<?php

use Zenstruck\Foundry\Test\GlobalState;
use Zenstruck\Foundry\Tests\Fixtures\Stories\CategoryStory;

require \dirname(__DIR__).'/vendor/autoload.php';

GlobalState::add(static function() {
    CategoryStory::load();
});
