<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Tests\Unit\ORM\ResetDatabase;

use DAMA\DoctrineTestBundle\Doctrine\DBAL\StaticDriver;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\KernelInterface;
use Zenstruck\Foundry\ORM\ResetDatabase\DamaDatabaseResetter;
use Zenstruck\Foundry\ORM\ResetDatabase\OrmResetter;

final class DamaDatabaseResetterTest extends TestCase
{
    private bool $keepStaticConnections;

    protected function setUp(): void
    {
        if (!\class_exists(StaticDriver::class)) {
            self::markTestSkipped('dama/doctrine-test-bundle is not installed.');
        }

        $this->keepStaticConnections = StaticDriver::isKeepStaticConnections();
        StaticDriver::setKeepStaticConnections(true);
    }

    protected function tearDown(): void
    {
        if (\class_exists(StaticDriver::class)) {
            StaticDriver::setKeepStaticConnections($this->keepStaticConnections);
        }
    }

    /**
     * @test
     */
    #[Test]
    public function it_re_enables_static_connections_when_the_reset_fails(): void
    {
        $decorated = $this->createStub(OrmResetter::class);
        $decorated->method('resetBeforeFirstTest')->willThrowException(new \RuntimeException('reset failed'));

        $resetter = new DamaDatabaseResetter($decorated, '/tmp');

        try {
            $resetter->resetBeforeFirstTest($this->createStub(KernelInterface::class));
            self::fail('The exception of the decorated resetter should be rethrown.');
        } catch (\RuntimeException $e) {
            self::assertSame('reset failed', $e->getMessage());
        }

        self::assertTrue(StaticDriver::isKeepStaticConnections());
    }
}
