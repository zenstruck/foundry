<?php

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\ResetDatabase;

class SomeClass extends KernelTestCase
{
    use ResetDatabase, OtherTrait;
}

class OtherClass extends KernelTestCase
{
    use OtherTrait, ResetDatabase;
}

class SomeOtherClass extends KernelTestCase
{
    use OtherTrait;
    use ResetDatabase;
}

?>
-----
<?php

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\ResetDatabase;

#[\Zenstruck\Foundry\Attribute\ResetDatabase]
class SomeClass extends KernelTestCase
{
    use OtherTrait;
}

#[\Zenstruck\Foundry\Attribute\ResetDatabase]
class OtherClass extends KernelTestCase
{
    use OtherTrait;
}

#[\Zenstruck\Foundry\Attribute\ResetDatabase]
class SomeOtherClass extends KernelTestCase
{
    use OtherTrait;
}

?>
