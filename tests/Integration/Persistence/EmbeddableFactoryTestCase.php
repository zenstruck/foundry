<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Tests\Integration\Persistence;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;
use Zenstruck\Foundry\Tests\Fixture\Model\Embeddable;
use Zenstruck\Foundry\Tests\Fixture\Model\WithEmbeddable;

use function Zenstruck\Foundry\factory;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
abstract class EmbeddableFactoryTestCase extends KernelTestCase
{
    use Factories, ResetDatabase;

    /**
     * @test
     */
    public function embed_one(): void
    {
        $factory = $this->withEmbeddableFactory();
        $object = $factory->create(['embeddable' => factory(Embeddable::class, ['prop1' => 'value1'])]);

        $this->assertSame('value1', $object->getEmbeddable()->getProp1());
        $factory::repository()->assert()->count(1);

        self::ensureKernelShutdown();

        $object = $factory::first();

        $this->assertSame('value1', $object->getEmbeddable()->getProp1());
    }

    /**
     * @test
     */
    public function can_find_using_embeddable_object(): void
    {
        $factory = $this->withEmbeddableFactory();
        $embeddableFactory = factory(Embeddable::class, ['prop1' => 'value1']);
        $factory->create(['embeddable' => $embeddableFactory]);

        $object = $factory::find(['embeddable' => $embeddableFactory]);

        $this->assertSame('value1', $object->getEmbeddable()->getProp1());

        $this->assertSame(0, $factory::count(['embeddable' => factory(Embeddable::class, ['prop1' => 'value2'])]));
    }

    /**
     * @test
     */
    public function can_use_embeddable_as_factory_parameter(): void
    {
        $factory = $this->withEmbeddableFactory();
        $factory->create(['embeddable' => $embeddable = new Embeddable('value1')]);

        $object = $factory::find(['embeddable' => $embeddable]);

        self::assertEquals($embeddable, $object->getEmbeddable());
    }

    /**
     * @return PersistentObjectFactory<WithEmbeddable>
     */
    abstract protected function withEmbeddableFactory(): PersistentObjectFactory;
}
