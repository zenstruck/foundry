<?php

namespace Zenstruck\Foundry\Utils\Rector\Tests\Fixtures;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Factory;
use Zenstruck\Foundry\Proxy;
use Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyObject;
use Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyPersistentObject;
use Zenstruck\Foundry\FactoryCollection;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyObjectModelFactory;
use Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyPersistentModelFactory;

class DummyKernelTestCase extends KernelTestCase
{
    use Factories;

    protected function setUp(): void
    {
        $this->enablePersist();
        $this->disablePersist();
    }

    /**
     * @param FactoryCollection<DummyPersistentObject> $factoryCollectionWithProxy
     * @param FactoryCollection<DummyObject> $factoryCollectionWithoutProxy
     */
    public function testSomething(FactoryCollection $factoryCollectionWithProxy, FactoryCollection $factoryCollectionWithoutProxy): void
    {
        DummyObjectModelFactory::new()->withAttributes(['published' => true])->object();
        DummyPersistentModelFactory::createOne()->object();

        /** @var Proxy<DummyPersistentObject> */
        $object = DummyPersistentModelFactory::createOne();
        $object->refresh();

        \Zenstruck\Foundry\create(SomeClass::class, []);
        \Zenstruck\Foundry\instantiate(SomeClass::class, ['published' => true]);
        \Zenstruck\Foundry\repository($object);

        \Zenstruck\Foundry\Factory::delayFlush(static fn() => true);
        \Zenstruck\Foundry\Test\TestState::configure(faker: null);

        Factory::faker();
    }
}

?>
-----
<?php

namespace Zenstruck\Foundry\Utils\Rector\Tests\Fixtures;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Persistence\Proxy;
use Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyObject;
use Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyPersistentObject;
use Zenstruck\Foundry\FactoryCollection;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyObjectModelFactory;
use Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyPersistentModelFactory;

class DummyKernelTestCase extends KernelTestCase
{
    use Factories;

    protected function setUp(): void
    {
        \Zenstruck\Foundry\Persistence\enable_persisting();
        \Zenstruck\Foundry\Persistence\disable_persisting();
    }

    /**
     * @param FactoryCollection<Proxy<DummyPersistentObject>> $factoryCollectionWithProxy
     * @param FactoryCollection<DummyObject> $factoryCollectionWithoutProxy
     */
    public function testSomething(FactoryCollection $factoryCollectionWithProxy, FactoryCollection $factoryCollectionWithoutProxy): void
    {
        DummyObjectModelFactory::new()->with(['published' => true]);
        DummyPersistentModelFactory::createOne()->_real();

        /** @var Proxy<DummyPersistentObject> */
        $object = DummyPersistentModelFactory::createOne();
        $object->_refresh();

        \Zenstruck\Foundry\Persistence\persist(SomeClass::class, []);
        \Zenstruck\Foundry\object(SomeClass::class, ['published' => true]);
        \Zenstruck\Foundry\Persistence\repository($object);

        \Zenstruck\Foundry\Persistence\flush_after(static fn() => true);
        \Zenstruck\Foundry\Test\UnitTestConfig::configure(faker: null);

        \Zenstruck\Foundry\faker();
    }
}

?>
