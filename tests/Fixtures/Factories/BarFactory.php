<?php

namespace Zenstruck\Foundry\Tests\Fixtures\Factories;

use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Tests\Fixtures\Entity\Bar;

/**
 * @author Benjamin Knecht <benjaminknecht@wearewondrous.com>
 */
final class BarFactory extends ModelFactory
{
    protected static function getClass(): string
    {
        return Bar::class;
    }

    protected function getDefaults(): array
    {
        return ['value' => 'Some Bar'];
    }
}
