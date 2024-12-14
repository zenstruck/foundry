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
use Zenstruck\Foundry\ArrayFactory as BaseArrayFactory;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ArrayFactory extends BaseArrayFactory
{
    public function __construct(private ?UrlGeneratorInterface $router = null)
    {
    }

    protected function defaults(): array
    {
        return [
            'router' => (bool) $this->router,
            'default1' => 'default value 1',
            'default2' => 'default value 2',
            'fake' => self::faker()->randomElement(['value']),
        ];
    }
}
