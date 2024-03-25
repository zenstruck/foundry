<?php

namespace Zenstruck\Foundry\Utils\Rector\Tests\Fixtures;

use PHPUnit\Framework\TestCase;
use Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyPersistentObject;
use Zenstruck\Foundry\FactoryCollection;
use Zenstruck\Foundry\Test\Factories;

class DummyTestCase extends TestCase
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

use PHPUnit\Framework\TestCase;
use Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyPersistentObject;
use Zenstruck\Foundry\FactoryCollection;
use Zenstruck\Foundry\Test\Factories;

class DummyTestCase extends TestCase
{
    use Factories;

    protected function setUp(): void
    {
    }
}

?>
