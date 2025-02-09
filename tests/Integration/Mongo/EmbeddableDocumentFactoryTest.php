<?php

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
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;
use Zenstruck\Foundry\Tests\Fixture\Document\WithEmbeddableDocument;
use Zenstruck\Foundry\Tests\Fixture\Model\Embeddable;
use Zenstruck\Foundry\Tests\Integration\Persistence\EmbeddableFactoryTestCase;
use Zenstruck\Foundry\Tests\Integration\RequiresMongo;

use function Zenstruck\Foundry\factory;
use function Zenstruck\Foundry\Persistence\persist;
use function Zenstruck\Foundry\Persistence\persistent_factory;
use function Zenstruck\Foundry\Persistence\repository;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class EmbeddableDocumentFactoryTest extends EmbeddableFactoryTestCase
{
    use RequiresMongo;

    /**
     * @test
     */
    #[Test]
    public function embed_many(): void
    {
        $document = persist(WithEmbeddableDocument::class, [
            'embeddable' => factory(Embeddable::class, ['prop1' => 'value1']),
            'embeddables' => factory(Embeddable::class, fn($i) => ['prop1' => 'value'.$i])->many(2),
        ]);

        $this->assertCount(2, $document->getEmbeddables());
        $this->assertSame('value1', $document->getEmbeddables()[0]?->getProp1());
        $this->assertSame('value2', $document->getEmbeddables()[1]?->getProp1());
        repository(WithEmbeddableDocument::class)->assert()->count(1);

        self::ensureKernelShutdown();

        $document = persistent_factory(WithEmbeddableDocument::class)::first();

        $this->assertCount(2, $document->getEmbeddables());
        $this->assertSame('value1', $document->getEmbeddables()[0]?->getProp1());
        $this->assertSame('value2', $document->getEmbeddables()[1]?->getProp1());
    }

    protected function withEmbeddableFactory(): PersistentObjectFactory
    {
        return persistent_factory(WithEmbeddableDocument::class); // @phpstan-ignore return.type
    }
}
