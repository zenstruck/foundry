<?php

use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;
use App\SomeClass;

class SomeFactory extends PersistentProxyObjectFactory
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
-----
<?php

use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;
use App\SomeClass;

/**
 * @extends \Zenstruck\Foundry\Persistence\PersistentObjectFactory<\App\SomeClass>
 */
class SomeFactory extends \Zenstruck\Foundry\Persistence\PersistentObjectFactory
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
