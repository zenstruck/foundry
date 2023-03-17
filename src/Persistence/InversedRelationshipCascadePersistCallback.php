<?php

declare(strict_types=1);

namespace Zenstruck\Foundry\Persistence;

use Zenstruck\Foundry\Factory;
use Zenstruck\Foundry\Proxy;

/**
 * @internal
 */
final class InversedRelationshipCascadePersistCallback implements PostPersistCallback
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
