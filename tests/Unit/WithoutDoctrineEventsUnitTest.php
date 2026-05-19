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

namespace Zenstruck\Foundry\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Tests\Fixture\DoctrineEvents\DoctrineEventsSubscriber;
use Zenstruck\Foundry\Tests\Fixture\DoctrineEvents\EntityForDoctrineEventsFactory;

final class WithoutDoctrineEventsUnitTest extends TestCase
{
    use Factories;

    /**
     * @test
     */
    #[Test]
    public function it_has_no_effect_in_unit_test_with_all_doctrine_events(): void
    {
        $entity = EntityForDoctrineEventsFactory::new()
            ->withoutDoctrineEvents()
            ->create(['name' => 'test']);

        self::assertSame('test', $entity->name);
    }

    /**
     * @test
     */
    #[Test]
    public function it_has_no_effect_in_unit_test_with_specific_doctrine_event_listener(): void
    {
        $entity = EntityForDoctrineEventsFactory::new()
            ->withoutDoctrineEvents(DoctrineEventsSubscriber::class)
            ->create(['name' => 'test']);

        self::assertSame('test', $entity->name);
    }
}
