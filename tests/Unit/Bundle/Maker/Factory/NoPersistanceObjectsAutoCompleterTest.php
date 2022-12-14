<?php

declare(strict_types=1);

namespace Zenstruck\Foundry\Tests\Unit\Bundle\Maker\Factory;

use PHPUnit\Framework\TestCase;
use Zenstruck\Foundry\Bundle\Maker\Factory\NoPersistanceObjectsAutoCompleter;

final class NoPersistanceObjectsAutoCompleterTest extends TestCase
{
    /**
     * @test
     */
    public function it_can_get_class_names_from_src_directory()
    {
        $classNames = (new NoPersistanceObjectsAutoCompleter(\realpath(__DIR__.'/../../../../../')))->getAutocompleteValues();

        // this is an arbitrary number, to ensure we propose enough classes
        // this check should not be "assertSame()" otherwise it should be updated as soon as a new class is added in src
        self::assertGreaterThan(30, \count($classNames));

        foreach ($classNames as $class) {
            if ('Zenstruck\Foundry\functions' === $class) {
                continue;
            }

            self::assertTrue(\class_exists($class));
            self::assertFalse(\trait_exists($class));
            self::assertFalse(\interface_exists($class));
        }
    }
}
