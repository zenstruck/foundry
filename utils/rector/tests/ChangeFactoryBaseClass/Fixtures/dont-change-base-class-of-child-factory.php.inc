<?php

use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;
use App\SomeClass;

class ParentFactory extends PersistentProxyObjectFactory
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

class ChildFactory extends ParentFactory
{
}
?>
-----
<?php

use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;
use App\SomeClass;

/**
 * @extends \Zenstruck\Foundry\Persistence\PersistentObjectFactory<\App\SomeClass>
 */
class ParentFactory extends \Zenstruck\Foundry\Persistence\PersistentObjectFactory
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

class ChildFactory extends ParentFactory
{
}
?>
