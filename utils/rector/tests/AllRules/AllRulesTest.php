<?php

declare(strict_types=1);

namespace Zenstruck\Foundry\Utils\Rector\Tests\AllRules;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Rector\Testing\PHPUnit\AbstractRectorTestCase;

final class AllRulesTest extends AbstractRectorTestCase
{
    #[Test]
    #[DataProvider('provideData')]
    public function test(string $filePath): void
    {
        $this->doTestFile($filePath);
    }

    public static function provideData(): \Iterator
    {
        return self::yieldFilesFromDirectory(__DIR__.'/Fixtures');
    }

    public function provideConfigFilePath(): string
    {
        return __DIR__.'/../../config/foundry-set.php';
    }
}
