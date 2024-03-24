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

namespace Zenstruck\Foundry\Utils\Rector\Tests\AddProxyToFactoryCollectionTypeInPhpDoc;

use Rector\Testing\PHPUnit\AbstractRectorTestCase;

final class RewriteFactoryPhpDocTest extends AbstractRectorTestCase
{
    /**
     * @dataProvider provideData
     *
     * @test
     */
    public function test(string $filePath): void
    {
        $this->doTestFile($filePath);
    }

    public static function provideData(): iterable
    {
        return self::yieldFilesFromDirectory(__DIR__.'/Fixtures');
    }

    public function provideConfigFilePath(): string
    {
        return __DIR__.'/config.php';
    }
}
