# Foundry

[![CI Status](https://github.com/zenstruck/foundry/workflows/CI/badge.svg)](https://github.com/zenstruck/foundry/actions?query=workflow%3ACI)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/zenstruck/foundry/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/zenstruck/foundry/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/zenstruck/foundry/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/zenstruck/foundry/?branch=master)

A model factory library for creating expressive, auto-completable, on-demand test fixtures with Symfony and Doctrine.

Traditionally, data fixtures are defined in one or more files outside of your tests. When writing tests using these
fixtures, your fixtures are a sort of a *black box*. There is no clear connection between the fixtures and what you
are testing.

Foundry allows each individual test to fully follow the [AAA](https://www.thephilocoder.com/unit-testing-aaa-pattern/)
("Arrange", "Act", "Assert") testing pattern. You create your fixtures using "factories" at the beginning of each test.
You only create fixtures that are applicable for the test. Additionally, these fixtures are created with only the
attributes required for the test - attributes that are not applicable are filled with random data. The created fixture
objects are wrapped in a "proxy" that helps with pre and post assertions. 

Let's look at an example:

```php
public function test_can_post_a_comment(): void
{
    // 1. "Arrange"
    $post = PostFactory::new() // New Post factory
        ->published()          // Make the post in a "published" state
        ->persist([            // Instantiate Post object and persist
            'slug' => 'post-a' // This test only requires the slug field - all other fields are random data
        ])
    ;
    
    // 1a. "Pre-Assertions"
    $this->assertCount(0, $post->getComments());

    // 2. "Act"
    $client = static::createClient();
    $client->request('GET', '/posts/post-a'); // Note the slug from the arrange step
    $client->submitForm('Add', [
        'comment[name]' => 'John',
        'comment[body]' => 'My comment',
    ]);

    // 3. "Assert"
    self::assertResponseRedirects('/posts/post-a');

    $this->assertCount(1, $post->getComments()); // $post is auto-refreshed before calling ->getComments()

    CommentFactory::repository()->assertExists([ // Doctrine repository wrapper with assertions
        'name' => 'John',
        'body' => 'My comment',
    ]);
}
```

## Documentation

1. [Installation](#installation)
2. [Test Traits](#test-traits)
3. [Faker](#faker)
4. [Sample Entities](#sample-entities)
5. [Anonymous Factories](#anonymous-factories)
    1. [Instantiate](#instantiate)
    2. [Persist](#persist)
    3. [Attributes](#attributes)
    4. [Events](#events)
    5. [Instantiator](#instantiator)
    6. [Immutable](#immutable)
    7. [Object Proxy](#object-proxy)
    8. [Repository Proxy](#repository-proxy)
6. [Model Factories](#model-factories)
    1. [Generate](#generate)
    2. [Usage](#usage)
    3. [States](#states)
    4. [Initialize](#initialize)
7. [Stories](#stories)
8. [Global State](#global-state)
9. [Performance Considerations](#performance-considerations)
    1. [DAMADoctrineTestBundle](#damadoctrinetestbundle)
    2. [Miscellaneous](#miscellaneous) 
10. [Credit](#credit)

### Installation

    $ composer require zenstruck/foundry --dev

To use the *Maker's*, ensure [Symfony MakerBundle](https://symfony.com/doc/current/bundles/SymfonyMakerBundle/index.html)
is installed and enable the packaged bundle:

```php
# config/bundles.php

return [
    // ...
    Zenstruck\Foundry\Bundle\ZenstruckFoundryBundle::class => ['dev' => true],
];
```

### Test Traits

Add the `Factories` trait for tests using factories:

```php
use Zenstruck\Foundry\Test\Factories;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class MyTest extends WebTestCase // TestCase must be an instance of KernelTestCase
{
    use Factories;
    
    // ...
}
```

This library requires that your database be reset before each test. The packaged `ResetDatabase` trait handles this for
you. Before the first test, it drops (if exists) and creates the test database. Before each test, it resets the schema.

```php
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class MyTest extends WebTestCase // TestCase must be an instance of KernelTestCase
{
    use ResetDatabase, Factories;
    
    // ...
}
```

**TIP**: Create a base TestCase for tests using factories to avoid adding the traits to every TestCase.

By default, `ResetDatabase` resets the default configured connection's database and default configured object manager's
schema. To customize the connection's and object manager's to be reset (or reset multiple connections/managers), set the
following environment variables:

```.env.test
FOUNDRY_RESET_CONNECTIONS=connection1,connection2
FOUNDRY_RESET_OBJECT_MANAGERS=manager1,manager2
```

### Faker

This library provides a wrapper for [fzaninotto/faker](https://github.com/fzaninotto/Faker) to help with generating
random data for your factories:

```php
use Zenstruck\Foundry\Factory;
use function Zenstruck\Foundry\faker;

Factory::faker()->name; // random name

// alternatively, use the helper function
faker()->email; // random email
```

**NOTE**: You can register your own `Faker\Generator`:

```php
// tests/bootsrap.php
// ...
Zenstruck\Foundry\Factory::registerFaker(
    Faker\Factory::create('fr_FR')
);
```

### Sample Entities

For the remainder of the documentation, the following sample entities will be used:

```php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\CategoryRepository")
 */
class Category
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $name;

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    // ... getters/setters
}
```

```php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\PostRepository")
 */
class Post
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $title;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $body;
    
    /**
     * @ORM\Column(type="datetime")
     */
    private $createdAt;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $publishedAt;

    /**
     * @ORM\ManyToOne(targetEntity=Category::class)
     * @ORM\JoinColumn
     */
    private $category;

    public function __construct(string $title)
    {
        $this->title = $title;
        $this->createdAt = new \DateTime('now');
    }

    // ... getters/setters
}
```

### Anonymous Factories

Create a *Factory* for a class with the following:

```php
use App\Entity\Post;
use Zenstruck\Foundry\Factory;
use function Zenstruck\Foundry\factory;

new Factory(Post::class); // instance of Factory

factory(Post::class); // alternatively, use the helper function
```

#### Instantiate

Instantiate a factory object with the following:

```php
use App\Entity\Post;
use Zenstruck\Foundry\Factory;
use function Zenstruck\Foundry\faker;
use function Zenstruck\Foundry\instantiate;
use function Zenstruck\Foundry\instantiate_many;

(new Factory(Post::class))->instantiate(); // instance of Post
(new Factory(Post::class))->instantiate(['title' => 'Post A']); // instance of Post with $title set to "Post A"

// array of 6 Post objects with random titles
(new Factory(Post::class))->instantiateMany(6, fn() => ['title' => faker()->sentence]);

// alternatively, use the helper functions
instantiate(Post::class, ['title' => 'Post A']);
instantiate_many(6, Post::class, fn() => ['title' => faker()->sentence]);
```

#### Persist

Instantiate a factory object and persist to the database with the following (see the [Object Proxy](#object-proxy)
section for more information on the returned `Proxy` object):

```php
use App\Entity\Post;
use Zenstruck\Foundry\Factory;
use function Zenstruck\Foundry\faker;
use function Zenstruck\Foundry\persist;
use function Zenstruck\Foundry\persist_many;

// instance of Zenstruck\Foundry\Proxy wrapping Post with title "Post A"
(new Factory(Post::class))->persist(['title' => 'Post A']);

// disable proxying (instance of Post)
(new Factory(Post::class))->persist(['title' => 'Post A'], false);

// array of 6 Post Proxy objects with random titles
(new Factory(Post::class))->persistMany(6, fn() => ['title' => faker()->sentence]);

// alternatively, use the helper functions
persist(Post::class, ['title' => 'Post A']);
persist_many(6, Post::class, fn() => ['title' => faker()->sentence]);
```

You can globally disable object proxying during persisting:

```php
// tests/bootstrap.php
// ...
Zenstruck\Foundry\Factory::proxyByDefault(false);
```

**NOTE**: The persist operations require that a `Doctrine\Persistence\ManagerRegistry` be registered with
`Zenstruck\Foundry\PersistenceManager` via `\Zenstruck\Foundry\PersistenceManager::register($registry)`. If using the
[`Factories`](#test-traits) test trait, this is handled for you.

#### Attributes

The attributes used to instantiate the object can be added several ways. Attributes can be an *array*, or a *callable*
that returns an array. Using a *callable* helps with ensuring random data as the callable is run during instantiate.

```php
use App\Entity\Category;
use App\Entity\Post;
use Zenstruck\Foundry\Factory;
use function Zenstruck\Foundry\persist;

$post = (new Factory(Post::class, ['title' => 'Post A']))
    ->withAttributes([
        'body' => 'Post A Body...',

        // can use snake case
        'published_at' => new \DateTime('now'), 

        // factories are automatically instantiated (and persisted if the outer Factory is persisted)
        'category' => new Factory(Category::class, ['name' => 'php']), 
    ])
    ->withAttributes([
        // can use kebab case
        'published-at' => new \DateTime('last week'),

        // Proxies are automatically converted to their wrapped object
        'category' => persist(Category::class, ['name' => 'symfony']),
    ])
    ->withAttributes(fn() => ['createdAt' => Factory::faker()->dateTime])
    ->instantiate(['title' => 'Different Title'])
;

$post->getTitle(); // "Different Title"
$post->getBody(); // "Post A Body..."
$post->getCategory()->getName(); // "symfony"
$post->getPublishedAt(); // \DateTime('last week')
$post->getCreatedAt(); // random \DateTime
```

#### Events

The following events can be added to factories. Multiple event callbacks can be added, they are run in the order
they were added.

```php
use Doctrine\Persistence\ObjectManager;
use Zenstruck\Foundry\Factory;

(new Factory(Post::class))
    ->beforeInstantiate(function(array $attributes): array {
        // $attributes is what will be used to instantiate the object, manipulate as required
        $attributes['title'] = 'Different title';

        return $attributes; // must return the final $attributes
    })
    ->afterInstantiate(function(Post $object, array $attributes): void {
        // $object is the instantiated object
        // $attributes contains the attributes used to instantiate the object and any extras
    })
    ->afterPersist(function(Post $object, array $attributes, ObjectManager $om) {
        // this event is only called if the object was persisted
        // $object is the persisted object
        // $attributes contains the attributes used to instantiate the object and any extras
        // $om is the ObjectManager used to persist $object
    })
    
    // multiple events are allowed
    ->beforeInstantiate(fn($attributes) => $attributes)
    ->afterInstantiate(function() {})
    ->afterPersist(function() {})
;
```

#### Instantiator

By default, objects are instantiated with the object's constructor. Attributes that match constructor arguments are
used. Remaining attributes are set to the object's matching properties (public/protected/private). Extra attributes
are ignored. You can customize the instantiator several ways:

```php
use Zenstruck\Foundry\Instantiator;
use Zenstruck\Foundry\Factory;

// set the instantiator for the current factory
(new Factory(Post::class))
    // instantiate by only using the constructor (no "force setting" attributes)
    ->instantiator(Instantiator::onlyConstructor())

    // instantiate the object without calling the constructor (only "force set" attributes
    ->instantiator(Instantiator::withoutConstructor())

    // "strict" mode - extra attributes cause an exception
    ->instantiator(Instantiator::default()->strict())
    ->instantiator(Instantiator::onlyConstructor()->strict())
    ->instantiator(Instantiator::withoutConstructor()->strict())

    // The instantiator is just a callable, you can provide your own
    ->instantiator(function(array $attibutes, string $class): object {
        return new Post(); // ... your own logic
    })
;
```

You can also customize the instantiator globally for all your factories (can still be overruled by factory instance
instantiators):

```php
// tests/bootstrap.php
// ...
Zenstruck\Foundry\Factory::registerDefaultInstantiator(
    Zenstruck\Foundry\Instantiator::withoutConstructor()
);
```

#### Immutable

Factory objects are immutable:

```php
use App\Post;
use Zenstruck\Foundry\Factory;

$factory = new Factory(Post::class);
$factory->withAttributes([]); // new object
$factory->instantiator(function () {}); // new object
$factory->beforeInstantiate(function () {}); // new object
$factory->afterInstantiate(function () {}); // new object
$factory->afterPersist(function () {}); // new object
```

#### Object Proxy

By default, objects persisted by a factory are wrapped in a special [Proxy](src/Proxy.php) object. These objects help
with your "post-act" test assertions. Almost all calls to Proxy methods, first refresh the object from the database
(even if your entity manager has been cleared).

```php
use App\Entity\Post;
use function Zenstruck\Foundry\persist;

$post = persist(Post::class, ['title' => 'My Title']); // instance of Zenstruck\Foundry\Proxy

// get the wrapped object
$post->object(); // instance of Post

// PHPUnit assertions
$post->assertPersisted();
$post->assertNotPersisted();

// delete from the database
$post->remove();

// call any Post methods - before calling, the object is auto-refreshed from the database
$post->getTitle(); // "My Title"

// set property and save to the database
$post->setTitle('New Title');
$post->save(); 

/**
 * CAVEAT - when calling multiple methods that change the object state, the previous state will be lost because
 * of auto-refreshing. Use "withoutAutoRefresh()" to overcome this.
 */
$post->withoutAutoRefresh();    // disable auto-refreshing
$post->refresh();               // manually refresh
$post->setTitle('New Title');   // won't be auto-refreshed
$post->setBody('New Body');     // won't be auto-refreshed
$post->save();                  // save changes
$post->withAutoRefresh();       // re-enable auto-refreshing

// set private/protected properties
$post->forceSet('createdAt', new \DateTime()); 
$post->forceSet('created_at', new \DateTime()); // can use snake case
$post->forceSet('created-at', new \DateTime()); // can use kebab case

/**
 * CAVEAT - when force setting multiple properties, the previous set's changes will be lost because
 * of auto-refreshing. Use "withoutAutoRefresh()" or forceSetAll() to overcome this.
 */
$post->forceSetAll([
    'title' => 'Different title',
    'createdAt' => new \DateTime(),
]);

// get private/protected properties
$post->forceGet('createdAt');
$post->forceGet('created_at');
$post->forceGet('created-at');

$post->repository(); // instance of Zenstruck\Foundry\RepositoryProxy wrapping PostRepository
$post->repository(false); // instance of un-proxied PostRepository
```

You can globally disable auto-refreshing of proxies:

```php
// tests/bootstrap.php
// ...
Zenstruck\Foundry\Proxy::autoRefreshByDefault(false);
```

#### Repository Proxy

This library provides a Repository Proxy that wraps your object repositories to provide useful assertions and methods:

```php
use App\Entity\Post;
use function Zenstruck\Foundry\repository;

// instance of Zenstruck\Foundry\RepositoryProxy that wraps App\Repository\PostRepository
$repository = repository(Post::class);

// PHPUnit assertions
$repository->assertEmpty();
$repository->assertCount(3);
$repository->assertCountGreaterThan(3);
$repository->assertCountGreaterThanOrEqual(3);
$repository->assertCountLessThan(3);
$repository->assertCountLessThanOrEqual(3);
$repository->assertExists(['title' => 'My Title']);
$repository->assertNotExists(['title' => 'My Title']);

// helpful methods
$repository->getCount(); // number of rows in the database table
$repository->first(); // get the first object (wrapped in a object proxy)
$repository->truncate(); // delete all rows in the database table

// instance of ObjectRepository (all returned objects are proxied)
$repository->find(1);                               // Proxy|Post|null
$repository->find(['title' => 'My Title']);         // Proxy|Post|null
$repository->findOneBy(['title' => 'My Title']);    // Proxy|Post|null
$repository->findAll();                             // Proxy[]|Post[]
$repository->findBy(['title' => 'My Title']);       // Proxy[]|Post[]

// can call methods on the underlying repository (returned objects are proxied)
$repository->findOneByTitle('My Title'); // Proxy|Post|null

// get repository without proxying
repository(Post::class, false); // instance of PostRepository
```

### Model Factories

You can create custom model factories to add IDE auto-completion and other useful features.

#### Generate

Create a model factory for one of your entities with the maker command:

    $ bin/console make:factory Post

**NOTE**: Calling `make:factory` without arguments displays a list of registered entities in your app to choose from.

Customize the generated model factory (if not using the maker command, this is what you will need to create manually):

```php
namespace App\Tests\Factories;

use App\Entity\Post;
use App\Repository\PostRepository;
use Zenstruck\Foundry\RepositoryProxy;
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;

/**
 * @method static Post make($attributes = [])
 * @method static Post[] makeMany(int $number, $attributes = [])
 * @method static Post|Proxy create($attributes = [], ?bool $proxy = null)
 * @method static Post[]|Proxy[] createMany(int $number, $attributes = [], ?bool $proxy = null)
 * @method static PostRepository|RepositoryProxy repository(bool $proxy = true)
 * @method Post instantiate($attributes = [])
 * @method Post[] instantiateMany(int $number, $attributes = [])
 * @method Post|Proxy persist($attributes = [], ?bool $proxy = null)
 * @method Post[]|Proxy[] persistMany(int $number, $attributes = [], ?bool $proxy = null)
 */
final class PostFactory extends ModelFactory
{
    protected function getDefaults(): array
    {
        return [
            'title' => self::faker()->unique()->sentence,
            'body' => self::faker()->sentence,
        ];
    }

    protected static function getClass(): string
    {
        return Post::class;
    }
}
```

**TIP**: It is best to have `getDefaults()` return the attributes to persist a valid object (all non-nullable fields).

#### Usage

Model factories extend `Zenstruck/Foundry/Factory` so all [methods and functionality](#anonymous-factories) are
available.

```php
use App\Tests\Factories\PostFactory;

$post = PostFactory::new()->persist(); // Proxy with random data from `getDefaults()`

$post->getTitle(); // getTitle() can be autocompleted by your IDE!

PostFactory::new()->persist(['title' => 'My Title']); // override defaults 
PostFactory::new(['title' => 'My Title'])->persist(); // alternative to above

// find a persisted object for the given attributes, if not found, create with the attributes
PostFactory::findOrCreate(['title' => 'My Title']); // instance of Proxy|Post

PostFactory::repository(); // Instance of RepositoryProxy|PostRepository

// instantiate objects (without persisting)
PostFactory::new()->instantiate(); // instance of Post
PostFactory::new()->instantiate(['title' => 'My Title']); // instance of Post with $title = 'My Title'
```

#### States

You can add any methods you want to your model factories (ie static methods that create an object in a certain way) but
you can add "states":

```php
namespace App\Tests\Factories;

use App\Entity\Post;
use Zenstruck\Foundry\ModelFactory;
use function Zenstruck\Foundry\persist;

final class PostFactory extends ModelFactory
{
    // ...

    public function published(): self
    {
        return $this->addState(['published_at' => self::faker()->dateTime]);
    }

    public function unpublished(): self
    {
        return $this->addState(['published_at' => null]);
    }

    public function withTags(string ...$tags): self
    {
        return $this->afterInstantiate(function (Post $post) use ($tags) {
            foreach ($tags as $tag) {
                $post->addTag(persist(Tag::class, ['name' => $tag])->object());
            }
        });
    }

    public function withViewCount(int $count = null): self
    {
        return $this->addState(function () use ($count) {
            return ['view_count' => $count ?? self::faker()->numberBetween(0, 10000)];
        });
    }
}
```

You can use states to make your tests very explicit to improve readability:

```php
$post = PostFactory::new()->unpublished()->persist();
$post = PostFactory::new()->withViewCount(3)->persist();
$post = PostFactory::new()->withTags('dev', 'design')->persist();

// combine multiple states
$post = PostFactory::new()
    ->withTags('dev')
    ->unpublished()
    ->persist()
;

// states that don't require arguments can be added as strings to PostFactory::new()
$post = PostFactory::new('published', 'withViewCount')->persist();
```

#### Initialize

You can override your model factory's `initialize()` method to add default state/logic:

```php
namespace App\Tests\Factories;

use App\Entity\Post;
use Zenstruck\Foundry\ModelFactory;
use function Zenstruck\Foundry\persist;

final class PostFactory extends ModelFactory
{
    // ...

    protected function initialize(): self
    {
        return $this
            ->published() // published by default
            ->instantiator(function (array $attributes) {
                return new Post(); // custom instantiation for this factory
            })
            ->afterPersist(function () {}) // default event for this factory
        ; 
    }
}
```

### Stories

If you find your test's *arrange* step is getting complex (loading lots of fixtures) or duplicated, you can extract
this *state* into *Stories*. You can then load the Story at the beginning of these tests.

Create a story using the maker command:

    $ bin/console make:story Post

This creates a *Story* object in `tests/Stories`. Modify the *build* method to set the state for this story:

```php
namespace App\Tests\Stories;

use App\Tests\Factories\PostFactory;
use Zenstruck\Foundry\Story;

final class PostStory extends Story
{
    protected function build(): void
    {
        // use "add" to have the object managed by the story and can be accessed in
        // tests and other stories via PostStory::postA()
        $this->add('postA', PostFactory::create(['title' => 'Post A']));

        // still persisted but not managed by the story
        PostFactory::create([
            'title' => 'Post D',
            'category' => CategoryStory::php(), // can use other stories
        ]);
    }
}
```

Use the new story in your tests:

```php
public function test_using_story(): void
{
    PostStory::load(); // loads the state defined in PostStory::build()

    PostStory::load(); // does nothing - already loaded

    PostStory::load()->get('postA'); // Proxy wrapping Post
    PostStory::load()->postA();      // alternative to above
    PostStory::postA();              // alternative to above

    PostStory::postA()->getTitle(); // "Post A"
}
```

**NOTE**: Story state objects are always proxies even if proxying was disabled.

**NOTE**: Story state and objects persisted by them are reset after each test.

### Global State

If you have an initial database state you want for all tests, you can set this in your `tests/bootstrap.php`:

```php
// tests/bootstrap.php
// ...

Zenstruck\Foundry\Test\GlobalState::add(function () {
    CategoryFactory::create(['name' => 'php']);
    CategoryFactory::create(['name' => 'symfony']);
});
```

To avoid your boostrap file from becoming too complex, it is best to wrap your global state into a [Story](#stories):

```php
// tests/bootstrap.php
// ...

Zenstruck\Foundry\Test\GlobalState::add(function () {
    GlobalStory::load();
});
```

**NOTE**: You can still access *Global State Stories* objects in your tests. They are still only loaded once.

### Performance Considerations

The following are possible options to improve the speed of your test suite.

#### DAMADoctrineTestBundle

This library integrates seamlessly with [DAMADoctrineTestBundle](https://github.com/dmaicher/doctrine-test-bundle) to
wrap each test in a transaction which dramatically reduces test time. This library's test suite runs 5x faster with
this bundle enabled.

Follow its documentation to install. Foundry's `ResetDatabase` trait detects when using the bundle and adjusts
accordingly. Your database is still reset before running your test suite but the schema isn't reset before each test
(just the first).

**NOTE**: If using [Global State](#global-state), it is persisted to the database (not in a transaction) before your
test suite is run. This could further improve test speed if you have a complex global state.

#### Miscellaneous

1. Disable debug mode when running tests. In your `.env.test` file, you can set `APP_DEBUG=0` to have your tests
run without debug mode. This can speed up your tests considerably. You will need to ensure you cache is cleared before
running the test suite. The best place to do this is in your `tests/bootstrap.php`:

    ```php
    // tests/bootstrap.php
    // ...
    if (false === (bool) $_SERVER['APP_DEBUG']) {
        // ensure fresh cache
        (new Symfony\Component\Filesystem\Filesystem())->remove(__DIR__.'/../var/cache/test');
    }
    ```

2. Reduce password encoder *work factor*. If you have a lot of tests that work with encoded passwords, this will cause
these tests to be unnecessarily slow. You can improve the speed by reducing the *work factor* of your encoder:

    ```yaml
    # config/packages/test/security.yaml
    encoders:
        # use your user class name here
        App\Entity\User:
            # This should be the same value as in config/packages/security.yaml
            algorithm: auto
            cost: 4 # Lowest possible value for bcrypt
            time_cost: 3 # Lowest possible value for argon
            memory_cost: 10 # Lowest possible value for argon
    ```

### Credit

The [AAA](https://www.thephilocoder.com/unit-testing-aaa-pattern/) style of testing was first introduced to me by
[Adam Wathan's](https://adamwathan.me/) excellent [Test Driven Laravel Course](https://course.testdrivenlaravel.com/).
The inspiration for this libraries API comes from [Laravel factories](https://laravel.com/docs/master/database-testing)
and [christophrumpel/laravel-factories-reloaded](https://github.com/christophrumpel/laravel-factories-reloaded).
