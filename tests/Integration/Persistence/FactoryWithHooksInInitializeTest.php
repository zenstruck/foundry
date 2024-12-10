<?php

declare(strict_types=1);

namespace Zenstruck\Foundry\Tests\Integration\Persistence;

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
    public function it_can_access_current_factory_in_hooks(): void
    {
        $address = WithHooksInInitializeFactory::new()->withoutPersisting()->create();

        self::assertSame('beforeInstantiate - afterInstantiate', $address->getCity());
    }
}
