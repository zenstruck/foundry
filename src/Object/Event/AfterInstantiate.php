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

namespace Zenstruck\Foundry\Object\Event;

use Zenstruck\Foundry\Factory;
use Zenstruck\Foundry\ObjectFactory;

/**
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 *
 * @phpstan-import-type Parameters from Factory
 */
final class AfterInstantiate
{
    public function __construct(
        public readonly object $object,
        /** @phpstan-var Parameters */
        public readonly array $parameters,
        /** @var ObjectFactory<object> */
        public readonly ObjectFactory $factory,
    ) {
    }
}
