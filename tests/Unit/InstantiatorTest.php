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
use Zenstruck\Foundry\Object\Instantiator;

final class InstantiatorTest extends TestCase
{
    /**
     * @test
     */
    #[Test]
    public function can_set_variadic_constructor_attributes(): void
    {
        $object = Instantiator::withConstructor()([
            'propA' => 'A',
            'propB' => ['B', 'C', 'D'],
        ], VariadicInstantiatorDummy::class);

        $this->assertSame('constructor A', $object->getPropA());
        $this->assertSame(['B', 'C', 'D'], $object->getPropB());
    }
}

class VariadicInstantiatorDummy
{
    private string $propA;

    /** @var array<array-key, string> */
    private array $propB;

    public function __construct(string $propA, string ...$propB)
    {
        $this->propA = 'constructor '.$propA;
        $this->propB = $propB;
    }

    public function getPropA(): string
    {
        return $this->propA;
    }

    /**
     * @return array<array-key, string>
     */
    public function getPropB(): array
    {
        return $this->propB;
    }
}
