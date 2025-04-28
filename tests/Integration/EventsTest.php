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

namespace Zenstruck\Foundry\Tests\Integration;

use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;
use Zenstruck\Foundry\Tests\Fixture\Events\FactoryWithEventListeners;

final class EventsTest extends KernelTestCase
{
    use Factories, RequiresORM, ResetDatabase;

    /**
     * @test
     */
    #[Test]
    public function it_can_call_hooks(): void
    {
        $address = FactoryWithEventListeners::createOne(['name' => 'events']);

        self::assertSame(
            <<<TXT
                events
                BeforeInstantiate
                BeforeInstantiate with Foundry attribute
                BeforeInstantiate global
                AfterInstantiate
                AfterInstantiate with Foundry attribute
                AfterInstantiate global
                AfterPersist
                AfterPersist with Foundry attribute
                AfterPersist global
                TXT,
            $address->name
        );
    }
}
