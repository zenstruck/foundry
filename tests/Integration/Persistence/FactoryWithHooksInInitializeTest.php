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

use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Tests\Fixture\Factories\WithHooksInInitializeFactory;

/**
 * @author Nicolas Philippe <nikophil@gmail.com>
 */
final class FactoryWithHooksInInitializeTest extends KernelTestCase
{
    use Factories;

    /**
     * @test
     */
    #[Test]
    public function it_can_access_current_factory_in_hooks(): void
    {
        $address = WithHooksInInitializeFactory::new()->withoutPersisting()->create();

        self::assertSame('beforeInstantiate - afterInstantiate', $address->getCity());
    }
}
