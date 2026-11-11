<?php

declare(strict_types=1);

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Tests\Fixture\DoctrineCascadeRelationship;

use PHPUnit\Metadata\Parser\AttributeParser;
use PHPUnit\Metadata\Parser\CachingParser;
use PHPUnit\Metadata\Parser\Registry;
use PHPUnit\Runner;
use PHPUnit\TextUI;

/**
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 */
final class PhpUnitTestExtension implements Runner\Extension\Extension
{
    public function bootstrap(
        TextUI\Configuration\Configuration $configuration,
        Runner\Extension\Facade $facade,
        Runner\Extension\ParameterCollection $parameters,
    ): void {
        // ULTRA HACKY: this swaps PHPUnit's internal metadata parser by reflection. None of it is covered
        // by PHPUnit's BC promise: it can break on ANY PHPUnit upgrade, even a patch release.
        //
        // PHPUnit has no extension point for this: it only multiplies a test from its own attributes,
        // does not tell a data provider which test it runs for, and concatenates the data providers
        // of a test instead of crossing them.
        //
        // CascadePersistPermutationsTest fails as soon as PHPUnit stops using this parser.
        (new \ReflectionProperty(Registry::class, 'instance'))->setValue(null, new CachingParser(new UsingRelationshipsParser(new AttributeParser())));
    }
}
