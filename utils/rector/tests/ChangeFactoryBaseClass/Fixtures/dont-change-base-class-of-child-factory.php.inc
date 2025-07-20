<?php

use App\SomeClass;
use Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyPersistentProxyFactory;

class ParentFactory extends DummyPersistentProxyFactory
{
    protected function defaults(): array|callable
    {
        return [];
    }

    public static function class(): string
    {
        return SomeClass::class;
    }
}
?>
