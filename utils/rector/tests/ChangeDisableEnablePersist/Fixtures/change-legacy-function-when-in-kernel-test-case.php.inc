<?php

namespace Zenstruck\Foundry\Utils\Rector\Tests\Fixtures;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyPersistentObject;
use Zenstruck\Foundry\FactoryCollection;
use Zenstruck\Foundry\Test\Factories;

class DummyKernelTestCase extends KernelTestCase
{
    use Factories;

    protected function setUp(): void
    {
        $this->enablePersist();
        $this->disablePersist();
    }
}

?>
-----
<?php

namespace Zenstruck\Foundry\Utils\Rector\Tests\Fixtures;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyPersistentObject;
use Zenstruck\Foundry\FactoryCollection;
use Zenstruck\Foundry\Test\Factories;

class DummyKernelTestCase extends KernelTestCase
{
    use Factories;

    protected function setUp(): void
    {
        \Zenstruck\Foundry\Persistence\enable_persisting();
        \Zenstruck\Foundry\Persistence\disable_persisting();
    }
}

?>
