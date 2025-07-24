<?php

use Zenstruck\Foundry\Persistence\Proxy;

function (): Proxy {};
fn(): Proxy => true;
?>
-----
<?php

use Zenstruck\Foundry\Persistence\Proxy;

function (): object {};
fn(): object => true;
?>
