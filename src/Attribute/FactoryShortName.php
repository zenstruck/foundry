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

namespace Zenstruck\Foundry\Attribute;

/**
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
final class FactoryShortName
{
    public function __construct(
        public readonly string $shortName,
        public readonly ?string $pluralName = null,
    ) {
    }
}
