<?php

namespace Zenstruck\Foundry\Tests\Functional\Bundle\Maker;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Filesystem\Filesystem;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
abstract class MakerTestCase extends KernelTestCase
{
    /**
     * @before
     */
    public function skipIfNotUsingFoundryBundle(): void
    {
        if (!\getenv('USE_FOUNDRY_BUNDLE')) {
            $this->markTestSkipped('ZenstruckFoundryBundle not enabled.');
        }

        if (!\getenv('USE_ORM') && !\getenv('USE_ODM')) {
            $this->markTestSkipped('Generating factories for classes not managed by doctrine is not supported.');
        }
    }

    /**
     * @before
     */
    public static function cleanupTmpDir(): void
    {
        (new Filesystem())->remove(self::tempDir());
    }

    protected static function tempDir(): string
    {
        return __DIR__.'/../../../Fixtures/tmp';
    }

    protected static function tempFile(string $path): string
    {
        return \sprintf('%s/%s', self::tempDir(), $path);
    }

    protected function expectedFile(string $file): string
    {
        return \sprintf('%s/../../../Fixtures/Maker/%s/%s', __DIR__, $this->getName(), $file);
    }

    protected function assertFileFromMakerSameAsExpectedFile(string $expectedFile, string $fileFromMaker): void
    {
        $this->assertFileExists($fileFromMaker);
        $this->assertFileEquals($expectedFile, $fileFromMaker);
    }
}
