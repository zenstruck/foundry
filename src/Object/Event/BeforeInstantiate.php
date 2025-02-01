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
final class BeforeInstantiate
{
    public function __construct(
        /** @phpstan-var Parameters */
        public array $parameters,
        /** @var class-string */
        public readonly string $objectClass,
        /** @var ObjectFactory<object> */
        public readonly ObjectFactory $factory,
    ) {
    }
}
