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

namespace Zenstruck\Foundry\Tests\Unit\Persistence;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Zenstruck\Foundry\Persistence\DoctrineEventsScope;
use Zenstruck\Foundry\Persistence\PersistenceManager;

/**
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 */
final class DoctrineEventsScopeTest extends TestCase
{
    /** @var list<array{string, array<mixed>}> */
    private array $disableCalls = [];

    /** @var list<int> */
    private array $restorations = [];

    private int $attempts = 0;

    protected function setUp(): void
    {
        $this->disableCalls = $this->restorations = [];
        $this->attempts = 0;
    }

    /**
     * @test
     */
    #[Test]
    public function it_is_open_until_closed(): void
    {
        $scope = $this->createScope();

        self::assertTrue($scope->isOpen());

        $scope->close();

        self::assertFalse($scope->isOpen());
    }

    /**
     * @test
     */
    #[Test]
    public function it_does_nothing_when_no_factory_requests_disabling(): void
    {
        $scope = $this->createScope();

        $scope->disable(SomeEntity::class, null);
        $scope->disable(OtherEntity::class, null);
        $scope->close();

        self::assertSame([], $this->disableCalls);
        self::assertSame([], $this->restorations);
    }

    /**
     * @test
     */
    #[Test]
    public function it_disables_immediately_and_restores_only_on_close(): void
    {
        $scope = $this->createScope();

        $scope->disable(SomeEntity::class, [SomeListener::class]);

        self::assertSame([[SomeEntity::class, [SomeListener::class]]], $this->disableCalls);
        self::assertSame([], $this->restorations);

        $scope->close();

        self::assertSame([0], $this->restorations);
    }

    /**
     * @test
     */
    #[Test]
    public function it_accumulates_classes_across_registrations(): void
    {
        $scope = $this->createScope();

        $scope->disable(SomeEntity::class, [SomeListener::class]);
        $scope->disable(OtherEntity::class, [OtherListener::class]);
        $scope->disable(SomeEntity::class, null);

        self::assertSame(
            [
                [SomeEntity::class, [SomeListener::class]],
                [OtherEntity::class, [SomeListener::class, OtherListener::class]],
                [SomeEntity::class, [SomeListener::class, OtherListener::class]],
            ],
            $this->disableCalls,
        );
    }

    /**
     * @test
     */
    #[Test]
    public function it_restores_in_reverse_order(): void
    {
        $scope = $this->createScope();

        $scope->disable(SomeEntity::class, [SomeListener::class]);
        $scope->disable(OtherEntity::class, null);
        $scope->close();

        self::assertSame([1, 0], $this->restorations);
    }

    /**
     * @test
     */
    #[Test]
    public function it_deduplicates_identical_registrations(): void
    {
        $scope = $this->createScope();

        $scope->disable(SomeEntity::class, [SomeListener::class]);
        $scope->disable(SomeEntity::class, [SomeListener::class]);
        $scope->disable(SomeEntity::class, null);
        $scope->close();

        self::assertCount(1, $this->disableCalls);
        self::assertSame([0], $this->restorations);
    }

    /**
     * @test
     */
    #[Test]
    public function it_ignores_class_order_when_deduplicating(): void
    {
        $scope = $this->createScope();

        $scope->disable(SomeEntity::class, [SomeListener::class, OtherListener::class]);
        $scope->disable(SomeEntity::class, [OtherListener::class, SomeListener::class]);

        self::assertCount(1, $this->disableCalls);
    }

    /**
     * @test
     */
    #[Test]
    public function an_empty_list_means_all_and_absorbs_specific_classes(): void
    {
        $scope = $this->createScope();

        $scope->disable(SomeEntity::class, [SomeListener::class]);
        $scope->disable(OtherEntity::class, []);

        self::assertSame(
            [
                [SomeEntity::class, [SomeListener::class]],
                [OtherEntity::class, []],
            ],
            $this->disableCalls,
        );
    }

    /**
     * @test
     */
    #[Test]
    public function it_closes_only_once(): void
    {
        $scope = $this->createScope();

        $scope->disable(SomeEntity::class, [SomeListener::class]);
        $scope->close();
        $scope->close();

        self::assertSame([0], $this->restorations);
    }

    /**
     * @test
     */
    #[Test]
    public function a_failed_registration_can_be_retried(): void
    {
        $persistenceManager = self::createStub(PersistenceManager::class);
        $persistenceManager->method('disableDoctrineEvents')->willReturnCallback(
            function(string $entityClass, array $disabledClasses): callable {
                if (1 === ++$this->attempts) {
                    throw new \RuntimeException('registration failed');
                }

                $this->disableCalls[] = [$entityClass, $disabledClasses];

                return static function(): void {};
            }
        );
        $scope = new DoctrineEventsScope($persistenceManager);

        try {
            $scope->disable(SomeEntity::class, [SomeListener::class]);
            self::fail('exception was not thrown');
        } catch (\RuntimeException) {
        }

        $scope->disable(SomeEntity::class, [SomeListener::class]);

        self::assertSame(2, $this->attempts);
        self::assertCount(1, $this->disableCalls);
    }

    private function createScope(): DoctrineEventsScope
    {
        $persistenceManager = self::createStub(PersistenceManager::class);
        $persistenceManager->method('disableDoctrineEvents')->willReturnCallback(
            function(string $entityClass, array $disabledClasses): callable {
                $index = \count($this->disableCalls);
                $this->disableCalls[] = [$entityClass, $disabledClasses];

                return function() use ($index): void {
                    $this->restorations[] = $index;
                };
            }
        );

        return new DoctrineEventsScope($persistenceManager);
    }
}

final class SomeEntity
{
}

final class OtherEntity
{
}

final class SomeListener
{
}

final class OtherListener
{
}
