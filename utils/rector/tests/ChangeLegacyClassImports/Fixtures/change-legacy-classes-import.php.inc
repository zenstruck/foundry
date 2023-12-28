<?php

use Zenstruck\Foundry\Proxy;
use Zenstruck\Foundry\Instantiator;
use Zenstruck\Foundry\RepositoryProxy;
use Zenstruck\Foundry\RepositoryAssertions;

class SomeClass
{
}

?>
-----
<?php

use Zenstruck\Foundry\Persistence\Proxy;
use Zenstruck\Foundry\Object\Instantiator;
use Zenstruck\Foundry\Persistence\RepositoryDecorator;
use Zenstruck\Foundry\Persistence\RepositoryAssertions;

class SomeClass
{
}

?>
