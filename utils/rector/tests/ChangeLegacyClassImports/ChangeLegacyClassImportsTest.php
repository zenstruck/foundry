<?php

declare(strict_types=1);

namespace Zenstruck\Foundry\Utils\Rector\Tests\ChangeLegacyClassUses;

use Rector\Testing\PHPUnit\AbstractRectorTestCase;

final class ChangeLegacyClassImportsTest extends AbstractRectorTestCase
{
    /**
     * @dataProvider provideData
     */
    public function test(string $filePath): void
    {
        $this->doTestFile($filePath);
    }

    public static function provideData(): \Iterator
    {
        return self::yieldFilesFromDirectory(__DIR__ . '/Fixtures');
    }

    public function provideConfigFilePath(): string
    {
        return __DIR__ . '/config.php';
    }
}
