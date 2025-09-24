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

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[\Attribute(\Attribute::TARGET_METHOD)]
final class AsFoundryHook extends AsEventListener
{
    public function __construct(
        /** @var class-string|null */
        public readonly ?string $objectClass = null,
        int $priority = 0,
    ) {
        parent::__construct(priority: $priority);
    }
}
