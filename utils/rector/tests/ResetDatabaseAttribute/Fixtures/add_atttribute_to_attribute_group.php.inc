<?php

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\ResetDatabase;

#[OtherAttribute]
class SomeClass extends KernelTestCase
{
    use ResetDatabase;
}

?>
-----
<?php

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\ResetDatabase;

#[OtherAttribute]
#[\Zenstruck\Foundry\Attribute\ResetDatabase]
class SomeClass extends KernelTestCase
{
}

?>
