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

namespace Zenstruck\Foundry\Tests\Integration\Mongo;

use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;
use Zenstruck\Foundry\Tests\Fixture\DoctrineEvents\DocumentForDoctrineEventsFactory;
use Zenstruck\Foundry\Tests\Fixture\DoctrineEvents\MongoDoctrineEventsListener;
use Zenstruck\Foundry\Tests\Integration\RequiresMongo;

use function Zenstruck\Foundry\Persistence\flush_after;

final class WithoutDoctrineEventsTest extends KernelTestCase
{
    use Factories, RequiresMongo, ResetDatabase;

    #[Test]
    public function testMongoEventsAreCalledByDefault(): void
    {
        $document = DocumentForDoctrineEventsFactory::createOne(['name' => 'test']);

        self::assertSame('test (from Mongo event)', $document->name);
    }

    #[Test]
    public function testItCanDisableAllMongoEvents(): void
    {
        $document = DocumentForDoctrineEventsFactory::new()
            ->withoutDoctrineEvents()
            ->create(['name' => 'test']);

        self::assertSame('test', $document->name);
    }

    #[Test]
    public function testItCanDisableSpecificMongoEventListener(): void
    {
        $document = DocumentForDoctrineEventsFactory::new()
            ->withoutDoctrineEvents(MongoDoctrineEventsListener::class)
            ->create(['name' => 'test']);

        self::assertSame('test', $document->name);
    }

    #[Test]
    public function testMongoEventsAreRestoredAfterCreation(): void
    {
        DocumentForDoctrineEventsFactory::new()
            ->withoutDoctrineEvents()
            ->create(['name' => 'first']);

        $document = DocumentForDoctrineEventsFactory::createOne(['name' => 'second']);

        self::assertSame('second (from Mongo event)', $document->name);
    }

    #[Test]
    public function testItThrowsWhenUsedInsideFlushAfter(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('withoutDoctrineEvents() cannot be used inside flush_after().');

        flush_after(static function(): void {
            DocumentForDoctrineEventsFactory::new()
                ->withoutDoctrineEvents()
                ->create(['name' => 'test']);
        });
    }
}
