<?php

namespace Zenstruck\Foundry\Utils\Rector\Tests\Fixtures;

use Zenstruck\Foundry\Object\Instantiator;

(new Instantiator())
    ->allowExtraAttributes(['some', 'fields'])
    ->alwaysForceProperties(['other', 'fields'])
    ->allowExtraAttributes()
    ->alwaysForceProperties()

?>
-----
<?php

namespace Zenstruck\Foundry\Utils\Rector\Tests\Fixtures;

use Zenstruck\Foundry\Object\Instantiator;

(\Zenstruck\Foundry\Object\Instantiator::withConstructor())
    ->allowExtra(...['some', 'fields'])
    ->alwaysForce(...['other', 'fields'])
    ->allowExtra()
    ->alwaysForce()

?>
