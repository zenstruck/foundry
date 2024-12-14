<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Tests\Fixture\Factories;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Zenstruck\Foundry\ObjectFactory;
use Zenstruck\Foundry\Tests\Fixture\Object1;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @extends ObjectFactory<Object1>
 */
final class Object1Factory extends ObjectFactory
{
    public function __construct(private ?UrlGeneratorInterface $router = null)
    {
    }

    public static function class(): string
    {
        return Object1::class;
    }

    protected function defaults(): array
    {
        return [
            'prop1' => $this->router ? 'router' : 'value1',
        ];
    }
}
