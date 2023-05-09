<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\BaseFactory;
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
        $this->assertSame('custom-value', BaseFactory::faker()->customValue());
    }
}
