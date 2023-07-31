<?php

use Zenstruck\Foundry\FactoryCollection;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;
use Zenstruck\Foundry\Proxy;
use Zenstruck\Foundry\RepositoryProxy;
use Zenstruck\Foundry\Tests\Fixtures\Entity\Contact;
use Zenstruck\Foundry\Tests\Fixtures\Factories\ContactFactory;
use function Zenstruck\Foundry\anonymous;
use function Zenstruck\Foundry\create;
use function Zenstruck\Foundry\create_many;

/**
 * @param PersistentObjectFactory<Contact> $factory
 * @return PersistentObjectFactory<Contact>
 */
function factory(PersistentObjectFactory $factory): PersistentObjectFactory
{
    return $factory;
}

/**
 * @param Proxy<Contact> $proxy
 */
function proxy(Proxy $proxy): Contact
{
    return $proxy->object();
}

/**
 * @param list<Proxy<Contact>> $proxies
 * @return list<Contact>
 */
function proxies(array $proxies): array
{
    return array_map(
        fn($proxy) => $proxy->object(),
        $proxies
    );
}

/**
 * @param RepositoryProxy<Contact> $proxy
 */
function repository(RepositoryProxy $proxy): Contact|null
{
    return $proxy->findOneBy([])?->object();
}

/**
 * @param FactoryCollection<Proxy<Contact>> $factoryCollection
 * @return list<Proxy<Contact>>
 */
function factory_collection(FactoryCollection $factoryCollection): array
{
    return $factoryCollection->create();
}

factory(ContactFactory::new());
factory(anonymous(Contact::class));

proxy(ContactFactory::new()->create());
proxy(ContactFactory::new()->create(['title' => 'foo']));
proxy(ContactFactory::new()->create(fn() => ['title' => 'foo']));
proxy(ContactFactory::createOne());
proxy(anonymous(Contact::class)->create());
proxy(create(Contact::class));

proxies(ContactFactory::new()->many(2)->create());
proxies(ContactFactory::createMany(2));
proxies(anonymous(Contact::class)->many(2)->create());
proxies(create_many(2, Contact::class));

repository(ContactFactory::repository());

factory_collection(ContactFactory::new()->many(2));
factory_collection(ContactFactory::new()->sequence([['title' => 'foo']]));
factory_collection(anonymous(Contact::class)->many(2));
factory_collection(anonymous(Contact::class)->sequence([['title' => 'foo']]));


