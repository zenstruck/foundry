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

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Zenstruck\Foundry\ObjectFactory;
use Zenstruck\Foundry\Persistence\Event\AfterPersist;
use Zenstruck\Foundry\Persistence\PersistenceManager;
use Zenstruck\Foundry\Persistence\PersistenceStrategy;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;
use Zenstruck\Foundry\Persistence\ResetDatabase\ResetDatabaseManager;
use Zenstruck\Foundry\Test\Behat\FactoryShortNameResolver;
use Zenstruck\Foundry\Test\Behat\ObjectAlreadyRegisteredException;
use Zenstruck\Foundry\Test\Behat\ObjectNotFoundException;
use Zenstruck\Foundry\Test\Behat\ObjectRegistry;

final class ObjectRegistryTest extends TestCase
{
    private ObjectRegistry $registry;
    private FactoryShortNameResolver $resolver;
    private PersistenceManager $persistenceManager;

    #[Test]
    public function it_stores_an_object(): void
    {
        $user = new User(id: 1, name: 'John');

        $this->registry->store($user, 'john', 'user');

        self::assertTrue($this->registry->has(User::class, 'john'));
    }

    #[Test]
    public function it_throws_when_storing_duplicate_object_name(): void
    {
        $user1 = new User(id: 1, name: 'John');
        $user2 = new User(id: 2, name: 'Jane');

        $this->registry->store($user1, 'john', 'user');

        $this->expectException(ObjectAlreadyRegisteredException::class);
        $this->expectExceptionMessage('Object "user john" is already registered in the ObjectRegistry.');

        $this->registry->store($user2, 'john', 'user');
    }

    #[Test]
    public function it_allows_same_name_for_different_classes(): void
    {
        $user = new User(id: 1, name: 'John');
        $post = new Post(id: 1, title: 'John');

        $this->registry->store($user, 'john', 'user');
        $this->registry->store($post, 'john', 'post');

        self::assertTrue($this->registry->has(User::class, 'john'));
        self::assertTrue($this->registry->has(Post::class, 'john'));
    }

    #[Test]
    public function it_checks_if_object_exists(): void
    {
        $user = new User(id: 1, name: 'John');
        $this->registry->store($user, 'john', 'user');

        self::assertTrue($this->registry->has(User::class, 'john'));
        self::assertFalse($this->registry->has(User::class, 'jane'));
        self::assertFalse($this->registry->has(Post::class, 'john'));
    }

    #[Test]
    public function it_gets_stored_object(): void
    {
        $user = new User(id: 1, name: 'John');
        $this->registry->store($user, 'john', 'user');

        $retrieved = $this->registry->get('user', 'john');

        self::assertSame($user, $retrieved);
    }

    #[Test]
    public function it_throws_when_getting_non_existent_object(): void
    {
        $this->expectException(ObjectNotFoundException::class);
        $this->expectExceptionMessage('Object "user john" was not found.');

        $this->registry->get('user', 'john');
    }

    #[Test]
    public function it_resets_all_stored_objects(): void
    {
        $user = new User(id: 1, name: 'John');
        $this->registry->store($user, 'john', 'user');

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

        $this->registry->store($user1, 'john', 'user');
        $this->registry->store($user2, 'jane', 'user');

        self::assertSame(2, $this->registry->lastIdFor('user'));
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
        $persistenceManager = $this->createPersistenceManager(
            static fn(): array => ['id1' => 1, 'id2' => 2]
        );
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
        $persistenceManager = $this->createPersistenceManager(
            static fn(): array => ['id' => ['invalid']]
        );
        $registry = new ObjectRegistry($this->resolver, $persistenceManager);

        $user = new User(id: 42, name: 'John');
        $event = new AfterPersist($user, [], $this->createStub(PersistentObjectFactory::class));

        $registry->storeLastId($event);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Wrong type for the id: expected int or string, got "array".');

        $registry->lastId();
    }

    protected function setUp(): void
    {
        $this->resolver = new FactoryShortNameResolver([new UserFactory()]);
        $this->persistenceManager = $this->createPersistenceManager();
        $this->registry = new ObjectRegistry($this->resolver, $this->persistenceManager);
    }

    /**
     * @param callable(object): array<string, mixed>|null $getIdentifierValuesCallback
     */
    private function createPersistenceManager(?callable $getIdentifierValuesCallback = null): PersistenceManager
    {
        $strategy = $this->createStub(PersistenceStrategy::class);
        $strategy->method('supports')->willReturn(true);
        $strategy->method('getIdentifierValues')->willReturnCallback(
            $getIdentifierValuesCallback ?? static function (object $object): array {
                assert($object instanceof User);

                return ['id' => $object->id];
            }
        );

        return new PersistenceManager([$strategy], new ResetDatabaseManager([], []));
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
