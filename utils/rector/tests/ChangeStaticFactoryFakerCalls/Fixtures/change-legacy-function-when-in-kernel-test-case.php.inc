<?php

use Zenstruck\Foundry\Factory;
use Zenstruck\Foundry\ModelFactory;

Some::faker();
Factory::faker();
ModelFactory::faker();

function someFunction(){
    Factory::faker();
    ModelFactory::faker();
}

class SomeClass {
    public function someMethod(){
        Factory::faker();
    }
}
?>
-----
<?php

use Zenstruck\Foundry\Factory;
use Zenstruck\Foundry\ModelFactory;

Some::faker();
\Zenstruck\Foundry\faker();
\Zenstruck\Foundry\faker();

function someFunction(){
    \Zenstruck\Foundry\faker();
    \Zenstruck\Foundry\faker();
}

class SomeClass {
    public function someMethod(){
        \Zenstruck\Foundry\faker();
    }
}
?>
