<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Test\Behat\Tests\Unit\Test\Behat;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;
use Zenstruck\Foundry\ObjectFactory;
use Zenstruck\Foundry\Persistence\IdentifierResolver;
use Zenstruck\Foundry\Story\Event\StateAddedToStory;
use Zenstruck\Foundry\Test\Behat\Exception\CompositeIdentifierNotSupported;
use Zenstruck\Foundry\Test\Behat\Exception\ObjectAlreadyRegistered;
use Zenstruck\Foundry\Test\Behat\Exception\ObjectNotFound;
use Zenstruck\Foundry\Test\Behat\FactoryShortNameResolver;
use Zenstruck\Foundry\Test\Behat\ObjectRegistry;

final class ObjectRegistryTest extends TestCase
{
    private ObjectRegistry $registry;
    private FactoryShortNameResolver $resolver;
    private IdentifierResolver $persistenceManager;

    protected function setUp(): void
    {
        $this->resolver = new FactoryShortNameResolver([new UserFactory()]);
        $this->persistenceManager = $this->createStub(IdentifierResolver::class);
        $this->persistenceManager->method('getIdentifierValues')->willReturnCallback(
            static function(object $object): array {
                \assert($object instanceof User || $object instanceof Post);

                return ['id' => $object->id];
            }
        );
        $this->registry = new ObjectRegistry($this->resolver, $this->persistenceManager);
        $this->registry->reset();
    }

    protected function tearDown(): void
    {
        ObjectRegistry::stopCapturingStoryStates();
    }

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
        $this->expectExceptionMessage('Object "john" is already registered for class "Zenstruck\Foundry\Test\Behat\Tests\Unit\Test\Behat\User".');

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
        $this->expectExceptionMessage('Object of class "Zenstruck\Foundry\Test\Behat\Tests\Unit\Test\Behat\User" with name "john" was not found.');

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
    public function it_resolves_string_id(): void
    {
        $user = new User(id: 'uuid-123', name: 'John');
        $this->registry->store($user, 'john');

        self::assertSame('uuid-123', $this->registry->idFor('user', 'john'));
    }

    #[Test]
    public function it_resolves_uuid_id_to_its_rfc4122_form(): void
    {
        $uuid = Uuid::v7();

        $persistenceManager = $this->createStub(IdentifierResolver::class);
        $persistenceManager->method('getIdentifierValues')->willReturn(['id' => $uuid]);

        $registry = new ObjectRegistry($this->resolver, $persistenceManager);
        $registry->reset();

        $registry->store(new User(id: 1, name: 'John'), 'john');

        self::assertSame($uuid->toRfc4122(), $registry->idFor('user', 'john'));
    }

    #[Test]
    public function it_stores_object_from_state_added_event(): void
    {
        ObjectRegistry::startCapturingStoryStates();

        $user = new User(id: 1, name: 'John');
        $event = new StateAddedToStory($user, 'john');

        $this->registry->storeAfterStateAddedToStory($event);

        self::assertTrue($this->registry->has(User::class, 'john'));
        self::assertSame($user, $this->registry->getByObjectClass(User::class, 'john'));
    }

    #[Test]
    public function it_throws_when_storing_duplicate_from_story_event(): void
    {
        ObjectRegistry::startCapturingStoryStates();

        $user1 = new User(id: 1, name: 'John');
        $user2 = new User(id: 2, name: 'Jane');

        $event1 = new StateAddedToStory($user1, 'duplicate');
        $event2 = new StateAddedToStory($user2, 'duplicate');

        $this->registry->storeAfterStateAddedToStory($event1);

        $this->expectException(ObjectAlreadyRegistered::class);
        $this->expectExceptionMessage('Object "duplicate" is already registered for class "Zenstruck\Foundry\Test\Behat\Tests\Unit\Test\Behat\User".');

        $this->registry->storeAfterStateAddedToStory($event2);
    }

    #[Test]
    public function it_allows_reregistering_a_name_from_a_previous_scenario(): void
    {
        $this->registry->store(new User(id: 1, name: 'John'), 'bg-object');

        ObjectRegistry::startScenario();

        $replacement = new User(id: 2, name: 'Jane');
        $this->registry->store($replacement, 'bg-object');

        self::assertSame($replacement, $this->registry->getByObjectClass(User::class, 'bg-object'));
    }

    #[Test]
    public function it_ignores_story_state_events_outside_a_behat_exercise(): void
    {
        $event = new StateAddedToStory(new User(id: 1, name: 'John'), 'john');

        $this->registry->storeAfterStateAddedToStory($event);
        $this->registry->storeAfterStateAddedToStory($event);

        self::assertFalse($this->registry->has(User::class, 'john'));
    }

    #[Test]
    public function it_throws_when_entity_has_multiple_identifiers(): void
    {
        $persistenceManager = $this->createStub(IdentifierResolver::class);
        $persistenceManager->method('getIdentifierValues')->willReturn(['id1' => 1, 'id2' => 2]);

        $registry = new ObjectRegistry($this->resolver, $persistenceManager);
        $registry->reset();

        $registry->store(new User(id: 42, name: 'John'), 'john');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot resolve the id: the entity must have exactly one identifier value, got 2 ("id1", "id2").');

        $registry->idFor('user', 'john');
    }

    #[Test]
    public function it_rejects_composite_identifiers_when_resolving_the_last_object(): void
    {
        $persistenceManager = $this->createStub(IdentifierResolver::class);
        $persistenceManager->method('getIdentifierFields')->willReturn(['foo', 'bar']);

        $registry = new ObjectRegistry($this->resolver, $persistenceManager);

        // a placeholder-agnostic domain exception: lastObjectFor() is also public API, callers
        // that know the placeholder syntax decorate it. Rejected before any database query.
        $this->expectException(CompositeIdentifierNotSupported::class);
        $this->expectExceptionMessage('must be identified by a single field to resolve its latest record, got 2 ("foo", "bar").');

        $registry->lastObjectFor('user');
    }

    #[Test]
    public function it_decorates_composite_identifier_errors_with_the_last_id_placeholder(): void
    {
        $persistenceManager = $this->createStub(IdentifierResolver::class);
        $persistenceManager->method('getIdentifierFields')->willReturn(['foo', 'bar']);

        $registry = new ObjectRegistry($this->resolver, $persistenceManager);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The "<foundry:lastId(user)>" placeholder cannot be resolved: ');

        $registry->resolveIdPlaceholders('/users/<foundry:lastId(user)>');
    }

    #[Test]
    public function it_throws_when_id_type_is_invalid(): void
    {
        $persistenceManager = $this->createStub(IdentifierResolver::class);
        $persistenceManager->method('getIdentifierValues')->willReturn(['id' => ['invalid']]);

        $registry = new ObjectRegistry($this->resolver, $persistenceManager);
        $registry->reset();

        $registry->store(new User(id: 42, name: 'John'), 'john');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Wrong type for the id: expected int, string or Uid, got "array".');

        $registry->idFor('user', 'john');
    }

    #[Test]
    public function it_resolves_all_id_placeholders_in_a_string(): void
    {
        $this->registry->store(new User(id: 7, name: 'John'), 'john');
        $this->registry->store(new User(id: 9, name: 'Jane'), 'jane');

        self::assertSame(
            '/users/9/friends/7',
            $this->registry->resolveIdPlaceholders('/users/<foundry:id(user, jane)>/friends/<foundry:id(user, john)>')
        );
    }

    #[Test]
    public function it_resolves_id_placeholders_with_quotes_and_extra_spaces(): void
    {
        $john = new User(id: 7, name: 'John');
        $this->registry->store($john, 'john doe');

        self::assertSame('/7', $this->registry->resolveIdPlaceholders('/<foundry:id( "user" , "john doe" )>'));
    }

    #[Test]
    public function it_leaves_strings_without_placeholders_untouched(): void
    {
        self::assertSame('/users/list', $this->registry->resolveIdPlaceholders('/users/list'));
    }

    #[Test]
    public function it_throws_on_malformed_placeholder(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Malformed id placeholder "<foundry:id(user)>": expected <foundry:lastId(factory)> or <foundry:id(factory, name)>.');

        $this->registry->resolveIdPlaceholders('/users/<foundry:id(user)>');
    }

    #[Test]
    public function it_leaves_unprefixed_placeholders_untouched(): void
    {
        $this->registry->store(new User(id: 7, name: 'John'), 'john');

        self::assertSame(
            '/users/<lastId>/friends/<id(user, john)>',
            $this->registry->resolveIdPlaceholders('/users/<lastId>/friends/<id(user, john)>')
        );
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
