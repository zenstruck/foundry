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

namespace Zenstruck\Foundry\Tests\Integration\Persistence;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;
use Zenstruck\Foundry\Tests\Fixture\Events\FactoryWithEventListeners;
use Zenstruck\Foundry\Tests\Integration\RequiresORM;

final class EventsTest extends KernelTestCase
{
    use Factories, RequiresORM, ResetDatabase;

    /**
     * @test
     */
    public function it_can_call_hooks(): void
    {
        $address = FactoryWithEventListeners::createOne(['name' => 'events']);

        self::assertSame(
            <<<TXT
                events
                BeforeInstantiate
                AfterInstantiate
                AfterPersist
                TXT,
            $address->name
        );
    }
}
