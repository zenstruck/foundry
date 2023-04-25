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

namespace Zenstruck\Foundry\Persistence;

use Zenstruck\Foundry\Factory;
use Zenstruck\Foundry\Proxy;

/**
 * @internal
 */
final class InversedRelationshipPostPersistCallback implements PostPersistCallback
{
    public function __construct(
        private Factory $factory,
        private string $relationshipField,
        private bool $isCollection,
    ) {
    }

    public function __invoke(Proxy $proxy): void
    {
        $this->factory->create([
            $this->relationshipField => $this->isCollection ? [$proxy] : $proxy,
        ]);

        $proxy->refresh();
    }
}
