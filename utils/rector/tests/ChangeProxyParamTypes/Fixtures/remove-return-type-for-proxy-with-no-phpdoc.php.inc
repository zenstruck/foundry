<?php

use Zenstruck\Foundry\Persistence\Proxy;

function someFunction(Proxy $param1, OtherObject $param2)
{

}
?>
-----
<?php

use Zenstruck\Foundry\Persistence\Proxy;

function someFunction(object $param1, OtherObject $param2)
{

}
?>
