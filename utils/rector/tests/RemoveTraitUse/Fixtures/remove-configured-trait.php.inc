<?php

use Vendor\OtherTrait;
use Vendor\TargetTrait;

class UsesBothTraits
{
    use TargetTrait, OtherTrait;
}

trait TraitUsesConfiguredTrait
{
    use TargetTrait;
}

?>
-----
<?php

use Vendor\OtherTrait;
use Vendor\TargetTrait;

class UsesBothTraits
{
    use OtherTrait;
}

trait TraitUsesConfiguredTrait
{
}

?>
