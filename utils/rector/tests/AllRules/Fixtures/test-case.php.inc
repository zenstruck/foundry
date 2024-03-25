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

use function Zenstruck\Foundry\instantiate;

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

        DummyPersistentModelFactory::new()->many(0, 1)->factory();

        /** @var Proxy<DummyPersistentObject> */
        $object = DummyPersistentModelFactory::createOne();
        $object->refresh();

        \Zenstruck\Foundry\create(SomeClass::class, []);
        \Zenstruck\Foundry\instantiate(SomeClass::class, ['published' => true]);
        \Zenstruck\Foundry\repository($object);

        \Zenstruck\Foundry\Factory::delayFlush(static fn() => true);
        \Zenstruck\Foundry\Test\TestState::configure(faker: null);

        Factory::faker();

        FactoryCollection::set(DummyObjectModelFactory::new(), 5);

        $unproxified1 = array_map(static fn (Proxy $proxy) => $proxy->object(), DummyObjectModelFactory::createMany(5));
        $unproxified2 = array_map(static fn (Proxy $proxy) => $proxy->object(), DummyObjectModelFactory::new()->many(5)->create());
        $unproxified3 = array_map(static fn (Proxy $proxy) => $proxy->object(), DummyPersistentModelFactory::createMany(5));
        $unproxified4 = array_map(static fn (Proxy $proxy) => $proxy->object(), DummyPersistentModelFactory::new()->many(5)->create());

        instantiate(DummyPersistentObject::class, [])->object();
        instantiate(DummyObject::class, [])->object();
    }
}

?>
-----
<?php

namespace Zenstruck\Foundry\Utils\Rector\Tests\Fixtures;

use Zenstruck\Foundry\Test\UnitTestConfig;
use function Zenstruck\Foundry\Persistence\persist_proxy;
use function Zenstruck\Foundry\object;
use function Zenstruck\Foundry\Persistence\repository;
use function Zenstruck\Foundry\Persistence\flush_after;
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
     * @param FactoryCollection<\Zenstruck\Foundry\Persistence\Proxy<DummyPersistentObject>> $factoryCollectionWithProxy
     * @param FactoryCollection<DummyObject> $factoryCollectionWithoutProxy
     */
    public function testSomething(FactoryCollection $factoryCollectionWithProxy, FactoryCollection $factoryCollectionWithoutProxy): void
    {
        DummyObjectModelFactory::new()->with(['published' => true]);
        DummyPersistentModelFactory::createOne()->_real();

        DummyPersistentModelFactory::new()->many(0, 1)->factory;

        /** @var Proxy<DummyPersistentObject> */
        $object = DummyPersistentModelFactory::createOne();
        $object->_refresh();

        persist_proxy(SomeClass::class, []);
        \Zenstruck\Foundry\Persistence\proxy(object(SomeClass::class, ['published' => true]));
        repository($object);

        flush_after(static fn() => true);
        UnitTestConfig::configure(faker: null);

        \Zenstruck\Foundry\faker();

        FactoryCollection::many(DummyObjectModelFactory::new(), 5);

        $unproxified1 = DummyObjectModelFactory::createMany(5);
        $unproxified2 = DummyObjectModelFactory::new()->many(5)->create();
        $unproxified3 = array_map(static fn (Proxy $proxy) => $proxy->_real(), DummyPersistentModelFactory::createMany(5));
        $unproxified4 = array_map(static fn (Proxy $proxy) => $proxy->_real(), DummyPersistentModelFactory::new()->many(5)->create());

        \Zenstruck\Foundry\Persistence\proxy(object(DummyPersistentObject::class, []))->_real();
        \Zenstruck\Foundry\Persistence\proxy(object(DummyObject::class, []))->_real();
    }
}

?>
