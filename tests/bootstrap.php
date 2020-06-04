<?php

use Zenstruck\Foundry\Test\GlobalState;
use Zenstruck\Foundry\Tests\Fixtures\Stories\TagStory;

require \dirname(__DIR__).'/vendor/autoload.php';

GlobalState::add(static function() {
    TagStory::load();
});
