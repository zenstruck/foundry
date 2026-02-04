<?php

declare(strict_types=1);

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Tests\Unit\Test\Behat;

use PHPUnit\Framework\Attributes\RequiresPhp;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Zenstruck\Foundry\ObjectFactory;
use Zenstruck\Foundry\Persistence\Event\AfterPersist;
use Zenstruck\Foundry\Persistence\PersistenceManager;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;
use Zenstruck\Foundry\Story\Event\StateAddedToStory;
use Zenstruck\Foundry\Test\Behat\FactoryShortNameResolver;
use Zenstruck\Foundry\Test\Behat\Exception\ObjectAlreadyRegistered;
use Zenstruck\Foundry\Test\Behat\Exception\ObjectNotFound;
use Zenstruck\Foundry\Test\Behat\ObjectRegistry;

/** @requires PHP 9 */
#[RequiresPhp('9')]
final class ObjectRegistryTest extends TestCase
{
    private ObjectRegistry $registry;
    private FactoryShortNameResolver $resolver;
    private PersistenceManager $persistenceManager;

    #[Test]
    public function it_stores_an_object(): void
    {
        $user = new User(id: 1, name: 'John');

        $this->registry->store($user, 'john');

        self::assertTrue($this->registry->has(User::class, 'john'));
    }

    #[Test]
    public function it_throws_when_storing_duplicate_object_name(): void
    {
        $user1 = new User(id: 1, name: 'John');
        $user2 = new User(id: 2, name: 'Jane');

        $this->registry->store($user1, 'john');

        $this->expectException(ObjectAlreadyRegistered::class);
        $this->expectExceptionMessage('Object "john" is already registered for class "Zenstruck\Foundry\Tests\Unit\Test\Behat\User".');

        $this->registry->store($user2, 'john');
    }

    #[Test]
    public function it_allows_same_name_for_different_classes(): void
    {
        $user = new User(id: 1, name: 'John');
        $post = new Post(id: 1, title: 'John');

        $this->registry->store($user, 'john');
        $this->registry->store($post, 'john');

        self::assertTrue($this->registry->has(User::class, 'john'));
        self::assertTrue($this->registry->has(Post::class, 'john'));
    }

    #[Test]
    public function it_checks_if_object_exists(): void
    {
        $user = new User(id: 1, name: 'John');
        $this->registry->store($user, 'john');

        self::assertTrue($this->registry->has(User::class, 'john'));
        self::assertFalse($this->registry->has(User::class, 'jane'));
        self::assertFalse($this->registry->has(Post::class, 'john'));
    }

    #[Test]
    public function it_gets_stored_object(): void
    {
        $user = new User(id: 1, name: 'John');
        $this->registry->store($user, 'john');

        $retrieved = $this->registry->getByObjectClass(User::class, 'john');

        self::assertSame($user, $retrieved);
    }

    #[Test]
    public function it_throws_when_getting_non_existent_object(): void
    {
        $this->expectException(ObjectNotFound::class);
        $this->expectExceptionMessage('Object of class "Zenstruck\Foundry\Tests\Unit\Test\Behat\User" with name "john" was not found.');

        $this->registry->getByObjectClass(User::class, 'john');
    }

    #[Test]
    public function it_resets_all_stored_objects(): void
    {
        $user = new User(id: 1, name: 'John');
        $this->registry->store($user, 'john');

        $this->registry->reset();

        self::assertFalse($this->registry->has(User::class, 'john'));
    }

    #[Test]
    public function it_stores_last_id_from_after_persist_event(): void
    {
        $user = new User(id: 42, name: 'John');
        $event = new AfterPersist($user, [], $this->createStub(PersistentObjectFactory::class));

        $this->registry->storeLastId($event);

        self::assertSame(42, $this->registry->lastId());
    }

    #[Test]
    public function it_stores_string_id_from_after_persist_event(): void
    {
        $user = new User(id: 'uuid-123', name: 'John');
        $event = new AfterPersist($user, [], $this->createStub(PersistentObjectFactory::class));

        $this->registry->storeLastId($event);

        self::assertSame('uuid-123', $this->registry->lastId());
    }

    #[Test]
    public function it_throws_when_no_last_id_available(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('No last id found.');

        $this->registry->lastId();
    }

    #[Test]
    public function it_resets_last_id(): void
    {
        $user = new User(id: 42, name: 'John');
        $event = new AfterPersist($user, [], $this->createStub(PersistentObjectFactory::class));
        $this->registry->storeLastId($event);

        $this->registry->reset();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('No last id found.');

        $this->registry->lastId();
    }

    #[Test]
    public function it_gets_last_id_for_specific_factory(): void
    {
        $user1 = new User(id: 1, name: 'John');
        $user2 = new User(id: 2, name: 'Jane');

        $this->registry->store($user1, 'john');
        $this->registry->store($user2, 'jane');

        self::assertSame(2, $this->registry->lastIdFor('user'));
    }

    #[Test]
    public function it_stores_object_from_state_added_event(): void
    {
        $user = new User(id: 1, name: 'John');
        $event = new StateAddedToStory($user, 'john');

        $this->registry->storeAfterStateAddedToStory($event);

        self::assertTrue($this->registry->has(User::class, 'john'));
        self::assertSame($user, $this->registry->getByObjectClass(User::class, 'john'));
    }

    #[Test]
    public function it_throws_when_storing_duplicate_from_story_event(): void
    {
        $user1 = new User(id: 1, name: 'John');
        $user2 = new User(id: 2, name: 'Jane');

        $event1 = new StateAddedToStory($user1, 'duplicate');
        $event2 = new StateAddedToStory($user2, 'duplicate');

        $this->registry->storeAfterStateAddedToStory($event1);

        $this->expectException(ObjectAlreadyRegistered::class);
        $this->expectExceptionMessage('Object "duplicate" is already registered for class "Zenstruck\Foundry\Tests\Unit\Test\Behat\User".');

        $this->registry->storeAfterStateAddedToStory($event2);
    }

    #[Test]
    public function it_throws_when_no_objects_for_factory(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('No object of type "user" found.');

        $this->registry->lastIdFor('user');
    }

    #[Test]
    public function it_throws_when_entity_has_multiple_identifiers(): void
    {
        $persistenceManager = $this->createStub(PersistenceManager::class);
        $persistenceManager->method('getIdentifierValues')->willReturn(['id1' => 1, 'id2' => 2]);

        $registry = new ObjectRegistry($this->resolver, $persistenceManager);

        $user = new User(id: 42, name: 'John');
        $event = new AfterPersist($user, [], $this->createStub(PersistentObjectFactory::class));

        $registry->storeLastId($event);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot get last id: generic entity must have exactly one identifier.');

        $registry->lastId();
    }

    #[Test]
    public function it_throws_when_id_type_is_invalid(): void
    {
        $persistenceManager = $this->createStub(PersistenceManager::class);
        $persistenceManager->method('getIdentifierValues')->willReturn(['id' => ['invalid']]);

        $registry = new ObjectRegistry($this->resolver, $persistenceManager);

        $user = new User(id: 42, name: 'John');
        $event = new AfterPersist($user, [], $this->createStub(PersistentObjectFactory::class));

        $registry->storeLastId($event);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Wrong type for the id: expected int or string, got "array".');

        $registry->lastId();
    }

    #[Test]
    public function it_checks_if_object_is_stored(): void
    {
        $user = new User(id: 1, name: 'John');
        $otherUser = new User(id: 2, name: 'Jane');

        $this->registry->store($user, 'john');

        self::assertTrue($this->registry->isStored($user));
        self::assertFalse($this->registry->isStored($otherUser));
    }

    #[Test]
    public function it_gets_name_for_stored_object(): void
    {
        $user = new User(id: 1, name: 'John');
        $this->registry->store($user, 'john-doe');

        self::assertSame('john-doe', $this->registry->getNameFor($user));
    }

    #[Test]
    public function it_throws_when_getting_name_for_unstored_object(): void
    {
        $user = new User(id: 1, name: 'John');

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Object is not stored in the registry.');

        $this->registry->getNameFor($user);
    }

    protected function setUp(): void
    {
        $this->resolver = new FactoryShortNameResolver([new UserFactory()]);
        $this->persistenceManager = $this->createStub(PersistenceManager::class);
        $this->persistenceManager->method('getIdentifierValues')->willReturnCallback(
            static function (object $object): array {
                assert($object instanceof User);

                return ['id' => $object->id];
            }
        );
        $this->registry = new ObjectRegistry($this->resolver, $this->persistenceManager);
        $this->registry->reset();
    }
}

final class User
{
    public function __construct(
        public int|string $id,
        public string $name,
    ) {
    }
}

final class Post
{
    public function __construct(
        public int $id,
        public string $title,
    ) {
    }
}

/** @extends ObjectFactory<User> */
final class UserFactory extends ObjectFactory
{
    public static function class(): string
    {
        return User::class;
    }

    protected function defaults(): array
    {
        return [
            'id' => 1,
            'name' => 'John Doe',
        ];
    }
}
