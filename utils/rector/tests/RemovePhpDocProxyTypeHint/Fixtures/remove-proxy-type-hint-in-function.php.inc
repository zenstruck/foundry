<?php

use App\SomeObject;
use Zenstruck\Foundry\Persistence\Proxy;

/**
 * @param Proxy<SomeObject> $param
 * @return Proxy<SomeObject>
 */
function function1()
{
}
?>
-----
<?php

use App\SomeObject;
use Zenstruck\Foundry\Persistence\Proxy;

/**
 * @param \App\SomeObject $param
 * @return \App\SomeObject
 */
function function1()
{
}
?>
