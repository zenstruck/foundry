<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Tests\Integration\Maker;

use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\String\Slugger\AsciiSlugger;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 * @group maker
 */
#[Group('maker')]
abstract class MakerTestCase extends KernelTestCase
{
    /**
     * @before
     */
    #[Before]
    public static function cleanupTempDir(): void
    {
        (new Filesystem())->remove(self::tempDir());
    }

    protected static function tempDir(): string
    {
        return __DIR__.'/../../Fixture/Maker/tmp';
    }

    protected static function tempFile(string $path): string
    {
        return \sprintf('%s/%s', self::tempDir(), $path);
    }

    protected function expectedFile(): string
    {
        $testName = \method_exists($this, 'getName') ? $this->getName() : $this->nameWithDataSet();

        $path = \sprintf(
            __DIR__.'/../../Fixture/Maker/expected/%s.php',
            (new AsciiSlugger())->slug($testName, '_'),
        );

        $this->assertFileExists($path);

        return \realpath($path); // @phpstan-ignore return.type
    }

    protected function assertFileFromMakerSameAsExpectedFile(string $fileFromMaker): void
    {
        $this->assertFileExists($fileFromMaker);
        $this->assertFileEquals($this->expectedFile(), $fileFromMaker, "File \"{$fileFromMaker}\" is different from expected file \"{$this->expectedFile()}\".");
    }
}
