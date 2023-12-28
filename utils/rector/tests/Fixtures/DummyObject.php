<?php

declare(strict_types=1);

namespace Zenstruck\Foundry\Utils\Rector\Tests\Fixtures;

use Doctrine\ORM\Mapping as ORM;

class DummyObject
{
    public int|null $id = null;

    private function __construct()
    {
    }

    public static function new(): static
    {
        return new self();
    }
}
