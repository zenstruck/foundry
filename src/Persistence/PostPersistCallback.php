<?php

declare(strict_types=1);

namespace Zenstruck\Foundry\Persistence;

use Zenstruck\Foundry\Proxy;

/**
 * @internal
 */
interface PostPersistCallback
{
    public function __invoke(Proxy $proxy): void;
}
