<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Tests\Fixture;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class Object1
{
    private string $prop1;
    private string $prop2;
    private ?string $prop3 = null;

    public function __construct(string $prop1, string $prop2 = 'default')
    {
        $this->prop1 = $prop1.'-constructor';
        $this->prop2 = $prop2.'-constructor';
    }

    public static function factory(string $prop1, string $prop2 = 'default'): self
    {
        return new self($prop1.'-named', $prop2.'-named');
    }

    public function getProp1(): string
    {
        return $this->prop1;
    }

    public function setProp1(string $prop1): void
    {
        $this->prop1 = $prop1.'-setter';
    }

    public function getProp2(): string
    {
        return $this->prop2;
    }

    public function setProp2(string $prop2): void
    {
        $this->prop2 = $prop2.'-setter';
    }

    public function getProp3(): ?string
    {
        return $this->prop3;
    }

    public function setProp3(string $prop3): void
    {
        $this->prop3 = $prop3.'-setter';
    }
}
