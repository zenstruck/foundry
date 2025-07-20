<?php

use Zenstruck\Foundry\Persistence\Proxy;

class SomeClass
{
    public function someMethod(): Proxy
    {

    }
}
?>
-----
<?php

use Zenstruck\Foundry\Persistence\Proxy;

class SomeClass
{
    public function someMethod(): object
    {

    }
}
?>
