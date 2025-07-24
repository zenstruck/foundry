<?php

use Zenstruck\Foundry\Persistence\Proxy;

function (Proxy $param) {};
fn(Proxy $param) => true;
?>
-----
<?php

use Zenstruck\Foundry\Persistence\Proxy;

function (object $param) {};
fn(object $param) => true;
?>
