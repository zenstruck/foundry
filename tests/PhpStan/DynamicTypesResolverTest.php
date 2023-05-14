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

namespace Zenstruck\Foundry\Tests\PhpStan;

use PHPStan\Testing\TypeInferenceTestCase;

final class DynamicTypesResolverTest extends TypeInferenceTestCase
{
    /**
     * @return iterable<mixed>
     */
    public function typesProvider(): iterable
    {
        yield from $this->gatherAssertTypes(__DIR__.'/../Fixtures/PhpStan/test-types.php');
    }

    /**
     * @test
     * @dataProvider typesProvider
     */
    public function it_can_resolve_tests(string $assertType, string $file, mixed ...$args): void
    {
        $this->assertFileAsserts($assertType, $file, ...$args);
    }

    public static function getAdditionalConfigFiles(): array
    {
        return [__DIR__.'/../../phpstan-foundry.neon'];
    }
}
