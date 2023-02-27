<?php

namespace Zenstruck\Foundry\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Factory;
use Zenstruck\Foundry\Test\Factories;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class FakerTest extends KernelTestCase
{
    use Factories;

    /**
     * @test
     */
    public function can_use_custom_provider(): void
    {
        $this->assertSame('custom-value', Factory::faker()->customValue());
    }
}
