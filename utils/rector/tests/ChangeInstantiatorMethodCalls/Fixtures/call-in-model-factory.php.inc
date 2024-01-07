<?php

namespace Zenstruck\Foundry\Utils\Rector\Tests\Fixtures;

use Zenstruck\Foundry\Object\Instantiator;
use Zenstruck\Foundry\ObjectFactory;

class DummyPersistentModelFactory extends ObjectFactory
{
    public function someMethod(): Instantiator
    {
        return (new Instantiator())
            ->allowExtraAttributes(['some', 'fields'])
            ->alwaysForceProperties(['other', 'fields'])
            ->allowExtraAttributes()
            ->alwaysForceProperties();
    }
}

?>
-----
<?php

namespace Zenstruck\Foundry\Utils\Rector\Tests\Fixtures;

use Zenstruck\Foundry\Object\Instantiator;
use Zenstruck\Foundry\ObjectFactory;

class DummyPersistentModelFactory extends ObjectFactory
{
    public function someMethod(): Instantiator
    {
        return (\Zenstruck\Foundry\Object\Instantiator::withConstructor())
            ->allowExtra(...['some', 'fields'])
            ->alwaysForce(...['other', 'fields'])
            ->allowExtra()
            ->alwaysForce();
    }
}

?>
