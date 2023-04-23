<?php

declare(strict_types=1);

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
    public function itCanResolveTests(string $assertType, string $file, mixed ...$args): void
    {
        $this->assertFileAsserts($assertType, $file, ...$args);
    }

    public static function getAdditionalConfigFiles(): array
    {
        return [__DIR__.'/../../phpstan-foundry.neon'];
    }
}
