# Foundry

[![CI Status](https://github.com/zenstruck/foundry/workflows/CI/badge.svg)](https://github.com/zenstruck/foundry/actions?query=workflow%3ACI)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/zenstruck/foundry/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/zenstruck/foundry/?branch=master)
[![Code Coverage](https://codecov.io/gh/zenstruck/foundry/branch/master/graph/badge.svg?token=77JIFYSUC5)](https://codecov.io/gh/zenstruck/foundry)
[![Latest Version](https://img.shields.io/packagist/v/zenstruck/foundry.svg)](https://packagist.org/packages/zenstruck/foundry)

Foundry makes creating fixtures data fun again, via an expressive, auto-completable, on-demand fixtures system with
Symfony and Doctrine:

```php
$post = PostFactory::new() // Create the factory for Post objects
    ->published()          // Make the post in a "published" state
    ->create([             // create & persist the Post object
        'slug' => 'post-a' // This Post object only requires the slug field - all other fields are random data
    ])
;
```

The factories can be used inside [DoctrineFixturesBundle](https://symfony.com/doc/master/bundles/DoctrineFixturesBundle/index.html)
to load fixtures or inside your tests, [where it has even more features](#using-in-your-tests).

Want to watch a screencast 🎥 about it? Check out https://symfonycasts.com/foundry

## Documentation

1. [Installation](#installation)
2. [Same Entities used in these Docs](#same-entities-used-in-these-docs)
3. [Model Factories](#model-factories)
    1. [Generate](#generate)
    2. [Using your Factory](#using-your-factory)
    3. [Reusable Model Factory "States"](#reusable-model-factory-states)
    4. [Attributes](#attributes)
    5. [Faker](#faker)
    6. [Events / Hooks](#events--hooks)
    7. [Initialization](#initialization)
    8. [Instantiation](#instantiation)
    9. [Immutable](#immutable)
    10. [Doctrine Relationships](#doctrine-relationships)
    11. [Factories as Services](#factories-as-services)
    12. [Anonymous Factories](#anonymous-factories)
    13. [Without Persisting](#without-persisting)
4. [Using with DoctrineFixturesBundle](#using-with-doctrinefixturesbundle)
5. [Using in your Tests](#using-in-your-tests)
    1. [Enable Foundry in your TestCase](#enable-foundry-in-your-testcase)
    2. [Object Proxy](#object-proxy)
        1. [Force Setting](#force-setting)
        2. [Auto-Refresh](#auto-refresh)
    3. [Repository Proxy](#repository-proxy)
    4. [Assertions](#assertions)
    5. [Global State](#global-state)
    6. [PHPUnit Data Providers](#phpunit-data-providers)
    7. [Performance](#performance)
        1. [DAMADoctrineTestBundle](#damadoctrinetestbundle)
        2. [Miscellaneous](#miscellaneous)
    8. [Non-Kernel Test](#non-kernel-tests)
    9. [Test-Only Configuration](#test-only-configuration)
    10. [Using without the Bundle](#using-without-the-bundle)
7. [Stories](#stories)
    1. [Stories as Services](#stories-as-services)
    2. [Story State](#story-state)
8. [Bundle Configuration](#bundle-configuration)
9. [Credit](#credit)

## Installation

    $ composer require zenstruck/foundry --dev

To use the `make:*` commands from this bundle, ensure
[Symfony MakerBundle](https://symfony.com/doc/current/bundles/SymfonyMakerBundle/index.html)
is installed.

*If not using Symfony Flex, be sure to enable the bundle in your **test**/**dev** environments.*

## Same Entities used in these Docs

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

## Model Factories

The nicest way to use Foundry is to generate one *factory* class per entity. You can skip this
and use [anonymous factories](#anonymous-factories), but *model factories* give you IDE auto-completion
and access to other useful features.

### Generate

Create a model factory for one of your entities with the maker command:

```
$ bin/console make:factory

> Entity class to create a factory for:
> Post

created: src/Factory/PostFactory.php

Next: Open your new factory and set default values/states.
```

This command will generate a `PostFactory` class that looks like this:

```php
// src/Factory/PostFactory.php

namespace App\Factory;

use App\Entity\Post;
use App\Repository\PostRepository;
use Zenstruck\Foundry\RepositoryProxy;
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;

/**
 * @method static Post|Proxy createOne(array $attributes = [])
 * @method static Post[]|Proxy[] createMany(int $number, $attributes = [])
 * @method static Post|Proxy findOrCreate(array $attributes)
 * @method static Post|Proxy random(array $attributes = [])
 * @method static Post|Proxy randomOrCreate(array $attributes = []))
 * @method static Post[]|Proxy[] randomSet(int $number, array $attributes = []))
 * @method static Post[]|Proxy[] randomRange(int $min, int $max, array $attributes = []))
 * @method static PostRepository|RepositoryProxy repository()
 * @method Post|Proxy create($attributes = [])
 */
final class PostFactory extends ModelFactory
{
    public function __construct()
    {
        parent::__construct();

        // TODO inject services if required (https://github.com/zenstruck/foundry#factories-as-services)
    }

    protected function getDefaults(): array
    {
        return [
            // TODO add your default values here (https://github.com/zenstruck/foundry#model-factories)
        ];
    }

    protected function initialize(): self
    {
        // see https://github.com/zenstruck/foundry#initialization
        return $this
            // ->afterInstantiate(function(Post $post) {})
        ;
    }

    protected static function getClass(): string
    {
        return Post::class;
    }
}
```

In the `getDefaults()`, you can return an array of all default values that any new object
should have. [Faker](#faker) is available to easily get random data:

```php
protected function getDefaults(): array
{
    return [
        // Symfony's property-access component is used to populate the properties
        // this means that setTitle() will be called or you can have a $title constructor argument
        'title' => self::faker()->unique()->sentence,
        'body' => self::faker()->sentence,
    ];
}
```

**TIP**: It is best to have `getDefaults()` return the attributes to persist a valid object
(all non-nullable fields).

### Using your Factory

```php
use App\Factory\PostFactory;

// create/persist Post with random data from `getDefaults()`
PostFactory::createOne();

// or provide values for some properties (others will be random)
PostFactory::createOne(['title' => 'My Title']);

// createOne() returns the persisted Post object wrapped in a Proxy object
$post = PostFactory::createOne();

// the "Proxy" magically calls the underlying Post methods and is type-hinted to "Post"
$title = $post->getTitle(); // getTitle() can be autocompleted by your IDE!

// if you need the actual Post object, use ->object()
$realPost = $post->object();

// create/persist 5 Posts with random data from getDefaults()
PostFactory::createMany(5); // returns Post[]|Proxy[]
PostFactory::createMany(5, ['title' => 'My Title']);

// find a persisted object for the given attributes, if not found, create with the attributes
PostFactory::findOrCreate(['title' => 'My Title']); // returns Post|Proxy

// get a random object that has been persisted
$post = PostFactory::random(); // returns Post|Proxy
$post = PostFactory::random(['author' => 'kevin']); // filter by the passed attributes

// or automatically persist a new random object if none exists
$post = PostFactory::randomOrCreate();
$post = PostFactory::randomOrCreate(['author' => 'kevin']); // filter by or create with the passed attributes

// get a random set of objects that have been persisted
$posts = PostFactory::randomSet(4); // array containing 4 "Post|Proxy" objects
$posts = PostFactory::randomSet(4, ['author' => 'kevin']); // filter by the passed attributes

// random range of persisted objects
$posts = PostFactory::randomRange(0, 5); // array containing 0-5 "Post|Proxy" objects
$posts = PostFactory::randomRange(0, 5, ['author' => 'kevin']); // filter by the passed attributes
```

### Reusable Model Factory "States"

You can add any methods you want to your model factories (ie static methods that create an object in a certain way) but
you can also add *states*:

```php
namespace App\Factory;

use App\Entity\Post;
use Zenstruck\Foundry\ModelFactory;

final class PostFactory extends ModelFactory
{
    // ...

    public function published(): self
    {
        // call setPublishedAt() and pass a random DateTime
        return $this->addState(['published_at' => self::faker()->dateTime]);
    }

    public function unpublished(): self
    {
        return $this->addState(['published_at' => null]);
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
// never use the constructor (i.e. "new PostFactory()"), but use the
// "new()" method. After defining the states, call "create()" to create
// and persist the model.
$post = PostFactory::new()->unpublished()->create();
$post = PostFactory::new()->withViewCount(3)->create();

// combine multiple states
$post = PostFactory::new()
    ->unpublished()
    ->withViewCount(10)
    ->create()
;

// states that don't require arguments can be added as strings to PostFactory::new()
$post = PostFactory::new('published', 'withViewCount')->create();
```

### Attributes

The attributes used to instantiate the object can be added several ways. Attributes can be an *array*, or a *callable*
that returns an array. Using a *callable* ensures random data as the callable is run for each object separately during
instantiation.

```php
use App\Entity\Category;
use App\Entity\Post;
use App\Factory\CategoryFactory;
use App\Factory\PostFactory;
use function Zenstruck\Foundry\faker;

// The first argument to "new()" allows you to overwrite the default
// values that are defined in the `PostFactory::getDefaults()`
$posts = PostFactory::new(['title' => 'Post A'])
    ->withAttributes([
        'body' => 'Post Body...',

        // CategoryFactory will be used to create a new Category for each Post
        'category' => CategoryFactory::new(['name' => 'php']), 
    ])
    ->withAttributes([
        // Proxies are automatically converted to their wrapped object
        'category' => CategoryFactory::createOne(),
    ])
    ->withAttributes(function() { return ['createdAt' => faker()->dateTime]; }) // see faker section below

    // create "2" Post's
    ->many(2)->create(['title' => 'Different Title'])
;

$post[0]->getTitle(); // "Different Title"
$post[0]->getBody(); // "Post Body..."
$post[0]->getCategory(); // random Category
$post[0]->getPublishedAt(); // \DateTime('last week')
$post[0]->getCreatedAt(); // random \DateTime

$post[1]->getTitle(); // "Different Title"
$post[1]->getBody(); // "Post Body..."
$post[1]->getCategory(); // random Category (different than above)
$post[1]->getPublishedAt(); // \DateTime('last week')
$post[1]->getCreatedAt(); // random \DateTime (different than above)
```

### Faker

This library provides a wrapper for [FakerPHP/faker](https://fakerphp.github.io/) to help with generating
random data for your factories:

```php
use Zenstruck\Foundry\Factory;
use function Zenstruck\Foundry\faker;

Factory::faker()->name; // random name

// alternatively, use the helper function
faker()->email; // random email
```

**NOTE**: You can register your own `Faker\Generator`:

```yaml
# config/packages/dev/zenstruck_foundry.yaml (see Bundle Configuration section about sharing this in the test environment)
zenstruck_foundry:
    faker:
        locale: fr_FR # set the locale
        # or
        service: my_faker # use your own instance of Faker\Generator for complete control
```

### Events / Hooks

The following events can be added to factories. Multiple event callbacks can be added, they are run in the order
they were added.

```php
use App\Factory\PostFactory;
use Zenstruck\Foundry\Proxy;

PostFactory::new()
    ->beforeInstantiate(function(array $attributes): array {
        // $attributes is what will be used to instantiate the object, manipulate as required
        $attributes['title'] = 'Different title';

        return $attributes; // must return the final $attributes
    })
    ->afterInstantiate(function(Post $object, array $attributes): void {
        // $object is the instantiated object
        // $attributes contains the attributes used to instantiate the object and any extras
    })
    ->afterPersist(function(Proxy $object, array $attributes) {
        /* @var Post $object */
        // this event is only called if the object was persisted
        // $proxy is a Proxy wrapping the persisted object
        // $attributes contains the attributes used to instantiate the object and any extras
    })

    // if the first argument is type-hinted as the object, it will be passed to the closure (and not the proxy)
    ->afterPersist(function(Post $object, array $attributes) {
        // this event is only called if the object was persisted
        // $object is the persisted Post object
        // $attributes contains the attributes used to instantiate the object and any extras
    })
    
    // multiple events are allowed
    ->beforeInstantiate(function($attributes) { return $attributes; })
    ->afterInstantiate(function() {})
    ->afterPersist(function() {})
;
```

You can also add hooks directly in your model factory class:

```php
protected function initialize(): self
{
    return $this
        ->beforePersist(function() {})
    ;
}
```

Read [Initialization](#initialization) to learn more about the `initialize()` method.

### Initialization

You can override your model factory's `initialize()` method to add default state/logic:

```php
namespace App\Factory;

use App\Entity\Post;
use Zenstruck\Foundry\ModelFactory;

final class PostFactory extends ModelFactory
{
    // ...

    protected function initialize(): self
    {
        return $this
            ->published() // published by default
            ->instantiateWith(function (array $attributes) {
                return new Post(); // custom instantiation for this factory
            })
            ->afterPersist(function () {}) // default event for this factory
        ; 
    }
}
```

**NOTE**: Be sure to chain the states/hooks off of `$this` because factories are [Immutable](#immutable).

### Instantiation

By default, objects are instantiated in the normal fashion, but using the object's constructor. Attributes
that match constructor arguments are used. Remaining attributes are set to the object using Symfony's
[PropertyAccess](https://symfony.com/doc/current/components/property_access.html) component (setters/public
properties). Any extra attributes cause an exception to be thrown.

You can customize the instantiator several ways:

```php
use App\Entity\Post;
use App\Factory\PostFactory;
use Zenstruck\Foundry\Instantiator;

// set the instantiator for the current factory
PostFactory::new()
    // instantiate the object without calling the constructor
    ->instantiateWith((new Instantiator())->withoutConstructor())

    // "foo" and "bar" attributes are ignored when instantiating
    ->instantiateWith((new Instantiator())->allowExtraAttributes(['foo', 'bar']))

    // all extra attributes are ignored when instantiating
    ->instantiateWith((new Instantiator())->allowExtraAttributes())

    // force set "title" and "body" when instantiating
    ->instantiateWith((new Instantiator())->alwaysForceProperties(['title', 'body']))

    // never use setters, always "force set" properties (even private/protected, does not use setter)
    ->instantiateWith((new Instantiator())->alwaysForceProperties())

    // can combine the different "modes"
    ->instantiateWith((new Instantiator())->withoutConstructor()->allowExtraAttributes()->alwaysForceProperties())

    // the instantiator is just a callable, you can provide your own
    ->instantiateWith(function(array $attibutes, string $class): object {
        return new Post(); // ... your own logic
    })
;
```

You can customize the instantiator globally for all your factories (can still be overruled by factory instance
instantiators):

```yaml
# config/packages/dev/zenstruck_foundry.yaml (see Bundle Configuration section about sharing this in the test environment)
zenstruck_foundry:
    instantiator:
        without_constructor: true # always instantiate objects without calling the constructor
        allow_extra_attributes: true # always ignore extra attributes
        always_force_properties: true # always "force set" properties
        # or
        service: my_instantiator # your own invokable service for complete control
```

### Immutable

Factory's are immutable:

```php
use App\Factory\PostFactory;

$factory = PostFactory::new();
$factory1 = $factory->withAttributes([]); // returns a new PostFactory object
$factory2 = $factory->instantiateWith(function () {}); // returns a new PostFactory object
$factory3 = $factory->beforeInstantiate(function () {}); // returns a new PostFactory object
$factory4 = $factory->afterInstantiate(function () {}); // returns a new PostFactory object
$factory5 = $factory->afterPersist(function () {}); // returns a new PostFactory object
```

### Doctrine Relationships

Assuming your entites follow the
[best practices for Doctrine Relationships](https://symfony.com/doc/current/doctrine/associations.html) and you are
using the [default instantiator](#instantiator), Foundry *just works* with doctrine relationships. There are some
nuances with the different relationships and how entities are created. The following tries to document these for
each relationship type.

#### Many-to-One

The following assumes the `Comment` entity has a many-to-one relationship with `Post`:

```php
use App\Factory\CommentFactory;
use App\Factory\PostFactory;

// Example 1: pre-create Post and attach to Comment
$post = PostFactory::createOne(); // instance of Proxy

CommentFactory::createOne(['post' => $post]);
CommentFactory::createOne(['post' => $post->object()]); // functionally the same as above

// Example 2: pre-create Posts and choose a random one
PostFactory::createMany(5); // create 5 Posts

CommentFactory::createOne(['post' => PostFactory::random()]);

// or create many, each with a different random Post
CommentFactory::createMany(
    5, // create 5 comments
    function() { // note the callback - this ensures that each of the 5 comments has a different Post
        return ['post' => PostFactory::random()]; // each comment set to a random Post from those already in the database
    }
);

// Example 3: create a separate Post for each Comment
CommentFactory::createMany(5, [
    // this attribute is an instance of PostFactory that is created separately for each Comment created
    'post' => PostFactory::new(),
]);

// Example 4: create multiple Comments with the same Post
CommentFactory::createMany(5, [
    'post' => PostFactory::createOne(), // note the "createOne()" here
]);
```

**TIP 1**: It is recommended that the only relationship you define in `ModelFactory::getDefaults()` is non-null
Many-to-One's.

**TIP 2**: It is also recommended that your `ModelFactory::getDefaults()` return a `Factory` and not the created entity:

```php
protected function getDefaults(): array
{
    return [
        // RECOMMENDED
        'post' => PostFactory::new(),
        'post' => PostFactory::new()->published(),

        // NOT RECOMMENDED - will potentially result in extra unintended Posts
        'post' => PostFactory::createOne(), 
        'post' => PostFactory::new()->published()->create(),
    ];
}
```

#### One-to-Many

The following assumes the `Post` entity has a one-to-many relationship with `Comment`:

```php
use App\Factory\CommentFactory;
use App\Factory\PostFactory;

// Example 1: Create a Post with 6 Comments
PostFactory::createOne(['comments' => CommentFactory::new()->many(6)]);

// Example 2: Create 6 Posts each with 4 Comments (24 Comments total)
PostFactory::createMany(6, ['comments' => CommentFactory::new()->many(4)]);

// Example 3: Create 6 Posts each with between 0 and 10 Comments
PostFactory::createMany(6, ['comments' => CommentFactory::new()->many(0, 10)]);
```

#### Many-to-Many

The following assumes the `Post` entity has a many-to-many relationship with `Tag`:

```php
use App\Factory\PostFactory;
use App\Factory\TagFactory;

// Example 1: pre-create Tags and attach to Post
$tags = TagFactory::createMany(3);

PostFactory::createOne(['tags' => $tags]);

// Example 2: pre-create Tags and choose a random set
TagFactory::createMany(10);

PostFactory::new()
    ->many(5) // create 5 posts
    ->create(function() { // note the callback - this ensures that each of the 5 posts has a different random set
        return ['tags' => TagFactory::randomSet(2)]; // each post uses 2 random tags from those already in the database
    })
;

// Example 3: pre-create Tags and choose a random range
TagFactory::createMany(10);

PostFactory::new()
    ->many(5) // create 5 posts
    ->create(function() { // note the callback - this ensures that each of the 5 posts has a different random range
        return ['tags' => TagFactory::randomRange(0, 5)]; // each post uses between 0 and 5 random tags from those already in the database
    })
;

// Example 4: create 3 Posts each with 3 unique Tags
PostFactory::createMany(3, ['tags' => TagFactory::new()->many(3)]);

// Example 5: create 3 Posts each with between 0 and 3 unique Tags
PostFactory::createMany(3, ['tags' => TagFactory::new()->many(0, 3)]);
```

### Factories as Services

If your factories require dependencies, you can define them as a service. The following example demonstrates a very
common use-case: encoding a password with the `UserPasswordEncoderInterface` service.

```php
// src/Factory/UserFactory.php

namespace App\Story;

use App\Entity\User;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Zenstruck\Foundry\ModelFactory;

final class UserFactory extends ModelFactory
{
    private $passwordEncoder;

    public function __construct(UserPasswordEncoderInterface $passwordEncoder)
    {
        parent::__construct();

        $this->passwordEncoder = $passwordEncoder;
    }

    protected function getDefaults(): array
    {
        return [
            'email' => self::faker()->unique()->safeEmail,
            'password' => '1234',
        ];
    }

    protected function initialize(): self
    {
        return $this
            ->afterInstantiate(function(User $user) {
                $user->setPassword($this->passwordEncoder->encodePassword($user, $user->getPassword()));
            })
        ;
    }

    protected static function getClass(): string
    {
        return User::class;
    }
}
```

If using a standard Symfony Flex app, this will be autowired/autoconfigured. If not, register the service and tag
with `foundry.factory`.

Use the factory as normal:

```php
UserFactory::createOne(['password' => 'mypass'])->getPassword(); // "mypass" encoded
UserFactory::createOne()->getPassword(); // "1234" encoded (because "1234" is set as the default password)
```

**NOTES**:
1. The provided bundle is required for factories as services.
2. If using `make:factory --test`, factories will be created in the `tests/Factory` directory which is not
autowired/autoconfigured in a standard Symfony Flex app. You will have to manually register these as
services.

### Anonymous Factories

Foundry can be used to create factories for entities that you don't have model factories for:

```php
use App\Entity\OtherEntity;
use Zenstruck\Foundry\Factory;
use function Zenstruck\Foundry\factory;
use function Zenstruck\Foundry\create;
use function Zenstruck\Foundry\create_many;
use function Zenstruck\Foundry\instantiate;
use function Zenstruck\Foundry\instantiate_many;

$factory = new Factory(OtherEntity::class);
$factory = factory(OtherEntity::class); // alternative to above

// has the same API as ModelFactory's
$factory->create(['field' => 'value']);
$factory->many(5)->create(['field' => 'value']);
$factory->instantiateWith(function () {});
$factory->beforeInstantiate(function () {});
$factory->afterInstantiate(function () {});
$factory->afterPersist(function () {});

// convenience functions
$entity = create(OtherEntity::class, ['field' => 'value']);
$entities = create_many(OtherEntity::class, 5, ['field' => 'value']);
```

### Without Persisting

Factories can also create objects without persisting them. This can be useful for unit tests where you just want to test
the behaviour of the actual object or for creating objects that are not entities. When created, they are still wrapped
in a `Proxy` to optionally save later.

```php
use App\Factory\PostFactory;
use App\Entity\OtherEntity;
use Zenstruck\Foundry\Factory;
use function Zenstruck\Foundry\instantiate;
use function Zenstruck\Foundry\instantiate_many;

$post = PostFactory::new()->withoutPersisting()->create(); // returns Post|Proxy
$post->setTitle('something else'); // do something with object
$post->save(); // persist the Post (save() is a method on Proxy)

$post = PostFactory::new()->withoutPersisting()->create()->object(); // actual Post object

$posts = PostFactory::new()->withoutPersisting()->many(5)->create(); // returns Post[]|Proxy[]

// anonymous factories:
$factory = new Factory(OtherEntity::class);

$entity = $factory->withoutPersisting()->create(['field' => 'value']); // returns OtherEntity|Proxy

$entity = $factory->withoutPersisting()->create(['field' => 'value'])->object(); // actual OtherEntity object

$entities = $factory->withoutPersisting()->many(5)->create(['field' => 'value']); // returns OtherEntity[]|Proxy[]

// convenience functions
$entity = instantiate(OtherEntity::class, ['field' => 'value']);
$entities = instantiate_many(OtherEntity::class, 5, ['field' => 'value']);
```

If you'd like your model factory to not persist by default, override its `initialize()` method to add this behaviour:

```php
protected function initialize(): self
{
    return $this
        ->withoutPersisting()
    ;
}
```

Now, after creating objects using this factory, you'd have to call `->save()` to actually persist them to the database.

**TIP**: If you'd like to disable persisting by default for all your model factories:

1. Create an abstract model factory that extends `Zenstruck\Foundry\ModelFactory`.
2. Override the `initialize()` method as shown above.
3. Have all your model factories extend from this.

## Using with DoctrineFixturesBundle

Foundry works out of the box with [DoctrineFixturesBundle](https://symfony.com/doc/master/bundles/DoctrineFixturesBundle/index.html).
You can simply use your factories and stories right within your fixture files:

```php
// src/DataFixtures/AppFixtures.php
namespace App\DataFixtures;

use App\Factory\CategoryFactory;
use App\Factory\CommentFactory;
use App\Factory\PostFactory;
use App\Factory\TagFactory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager)
    {
        // create 10 Category's
        CategoryFactory::createMany(10);

        // create 20 Tag's
        TagFactory::createMany(20);

        // create 50 Post's
        PostFactory::createMany(50, function() {
            return [
                // each Post will have a random Category (chosen from those created above)
                'category' => CategoryFactory::random(),

                // each Post will have between 0 and 6 Tag's (chosen from those created above)
                'tags' => TagFactory::randomRange(0, 6),

                // each Post will have between 0 and 10 Comment's that are created new
                'comments' => CommentFactory::new()->many(0, 10),
            ];
        });
    }
}
```

Run the [`doctrine:fixtures:load`](https://symfony.com/doc/master/bundles/DoctrineFixturesBundle/index.html#loading-fixtures)
as normal to seed your database.

## Using in your Tests

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
        ->create([             // Instantiate Post object and persist
            'slug' => 'post-a' // This test only requires the slug field - all other fields are random data
        ])
    ;
    
    // 1a. "Pre-Assertions"
    $this->assertCount(0, $post->getComments());

    // 2. "Act"
    static::ensureKernelShutdown(); // creating factories boots the kernel; shutdown before creating the client
    $client = static::createClient();
    $client->request('GET', '/posts/post-a'); // Note the slug from the arrange step
    $client->submitForm('Add', [
        'comment[name]' => 'John',
        'comment[body]' => 'My comment',
    ]);

    // 3. "Assert"
    self::assertResponseRedirects('/posts/post-a');

    $this->assertCount(1, $post->refresh()->getComments()); // Refresh $post from the database and call ->getComments()

    CommentFactory::repository()->assertExists([ // Doctrine repository wrapper with assertions
        'name' => 'John',
        'body' => 'My comment',
    ]);
}
```

### Enable Foundry in your TestCase

Add the `Factories` trait for tests using factories:

```php
use Zenstruck\Foundry\Test\Factories;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class MyTest extends WebTestCase
{
    use Factories;

    public function test_1(): void
    {
        // if using the test client, create before creating factories
        // (creating the client requires the kernel be shutdown and creating
        // factories boots the kernel)
        $client = self::createClient();

        $post = PostFactory::createOne();

        // ...
    }

    public function test_2(): void
    {
        $post = PostFactory::createOne();

        // if you want to create your factories before creating the client,
        // you will need to shut down the kernel first.
        self::ensureKernelShutdown();
        $client = self::createClient();

        // ...
    }
}
```

This library requires that your database be reset before each test. The packaged `ResetDatabase` trait handles this for
you. Before the first test, it drops (if exists) and creates the test database. Before each test, it resets the schema.

```php
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class MyTest extends WebTestCase
{
    use ResetDatabase, Factories;
    
    // ...
}
```

**TIP**: Create a base TestCase for tests using factories to avoid adding the traits to every TestCase.

**NOTE**: If your tests [are not persisting](#without-persisting) the objects they create, these test traits are not
required.

By default, `ResetDatabase` resets the default configured connection's database and default configured object manager's
schema. To customize the connection's and object manager's to be reset (or reset multiple connections/managers), set the
following environment variables:

```
# .env.test

FOUNDRY_RESET_CONNECTIONS=connection1,connection2
FOUNDRY_RESET_OBJECT_MANAGERS=manager1,manager2
```

### Object Proxy

Objects created by a factory are wrapped in a special *Proxy* object. These objects allow your doctrine entities
to have [Active Record](https://en.wikipedia.org/wiki/Active_record_pattern) *like* behavior:

```php
use App\Factory\PostFactory;

$post = PostFactory::createOne()->create(['title' => 'My Title']); // instance of Zenstruck\Foundry\Proxy

// get the wrapped object
$realPost = $post->object(); // instance of Post

// call any Post method
$post->getTitle(); // "My Title"

// set property and save to the database
$post->setTitle('New Title');
$post->save();

// refresh from the database
$post->refresh();

// delete from the database
$post->remove();

$post->repository(); // repository proxy wrapping PostRepository (see Repository Proxy section below)
```

#### Force Setting

Object proxies have helper methods to access non-public properties of the object they wrap:

```php
// set private/protected properties
$post->forceSet('createdAt', new \DateTime());

// get private/protected properties
$post->forceGet('createdAt');
```

#### Auto-Refresh

Object proxies have the option to enable *auto refreshing* that removes the need to call `->refresh()` before calling
methods on the underlying object. When auto-refresh is enabled, most calls to proxy objects first refresh the wrapped
object from the database.

```php
use App\Factory\PostFactory;

$post = PostFactory::new(['title' => 'Original Title'])
    ->create()
    ->enableAutoRefresh()
;

// ... logic that changes the $post title to "New Title" (like your functional test)

$post->getTitle(); // "New Title" (equivalent to $post->refresh()->getTitle())
```

Without auto-refreshing enabled, the above call to `$post->getTitle()` would return "Original Title".

**NOTE**: A situation you need to be aware of when using auto-refresh is that all methods refresh the object first. If
changing the object's state via multiple methods (or multiple force-sets), the previous changes will be lost:

```php
use App\Factory\PostFactory;

$post = PostFactory::new(['title' => 'Original Title', 'body' => 'Original Body'])
    ->create()
    ->enableAutoRefresh()
;

$post->setTitle('New Title');
$post->setBody('New Body'); // this causes the object to be refreshed, which means the "New Title" title was replaced by the database contents
$post->save();

$post->getBody(); // "New Body"
$post->getTitle(); // "Original Title" !! because the subsequent call to ->setBody() "auto-refreshed" the object
```

To overcome this, you need to first disable auto-refreshing, then re-enable after making/saving the changes:

```php
use App\Entity\Post;
use App\Factory\PostFactory;

$post = PostFactory::new(['title' => 'Original Title', 'body' => 'Original Body'])
    ->create()
    ->enableAutoRefresh()
;

$post->disableAutoRefresh();
$post->setTitle('New Title'); // or using ->forceSet('title', 'New Title')
$post->setBody('New Body'); // or using ->forceSet('body', 'New Body')
$post->enableAutoRefresh();
$post->save();

$post->getBody(); // "New Body"
$post->getTitle(); // "New Title"

// alternatively, use the ->withoutAutoRefresh() helper which first disables auto-refreshing, then re-enables after
// executing the callback.
$post->withoutAutoRefresh(function (Post $post) { // can pass either Post or Proxy to the callback
    $post->setTitle('New Title');
    $post->setBody('New Body');
});
$post->save();

// if force-setting properties, you can use the ->forceSetAll() helper:
$post->forceSetAll([
    'title' => 'New Title',
    'body' => 'New Body',
]);
$post->save();
```

**NOTE**: You can optionally enable auto-refreshing globally to have every proxy auto-refreshable by default. With this
enabled, you will have to *opt-out* of auto-refreshing as opposed to the default, which is *opt-in*. Be aware of the
above situation before enabling.

```yaml
# config/packages/dev/zenstruck_foundry.yaml (see Bundle Configuration section about sharing this in the test environment)
zenstruck_foundry:
    auto_refresh_proxies: true
```

### Repository Proxy

This library provides a *Repository Proxy* that wraps your object repositories to provide useful assertions and methods:

```php
use App\Entity\Post;
use App\Factory\PostFactory;
use function Zenstruck\Foundry\repository;

// instance of RepositoryProxy that wraps PostRepository
$repository = PostFactory::repository();

// alternative to above for proxying repository you haven't created model factories for
$repository = repository(Post::class);

// helpful methods - all returned object(s) are proxied
$repository->count(); // number of rows in the database table
count($repository); // equivalent to above (RepositoryProxy implements \Countable)
$repository->first(); // get the first object (assumes an auto-incremented "id" column)
$repository->first('createdAt'); // assuming "createdAt" is a datetime column, this will return latest object
$repository->last(); // get the last object (assumes an auto-incremented "id" column)
$repository->last('createdAt'); // assuming "createdAt" is a datetime column, this will return oldest object
$repository->truncate(); // delete all rows in the database table
$repository->random(); // get a random object
$repository->random(['author' => 'kevin']); // get a random object filtered by the passed criteria
$repository->randomSet(5); // get 5 random objects
$repository->randomSet(5, ['author' => 'kevin']); // get 5 random objects filtered by the passed criteria
$repository->randomRange(0, 5); // get 0-5 random objects
$repository->randomRange(0, 5, ['author' => 'kevin']); // get 0-5 random objects filtered by the passed criteria

// instance of ObjectRepository - all returned object(s) are proxied
$repository->find(1); // Proxy|Post|null
$repository->find(['title' => 'My Title']); // Proxy|Post|null
$repository->findOneBy(['title' => 'My Title']); // Proxy|Post|null
$repository->findAll(); // Proxy[]|Post[]
iterator_to_array($repository); // equivalent to above (RepositoryProxy implements \IteratorAggregate)
$repository->findBy(['title' => 'My Title']); // Proxy[]|Post[]

// can call methods on the underlying repository - returned object(s) are proxied
$repository->findOneByTitle('My Title'); // Proxy|Post|null
```

### Assertions

Both object and repository proxy's have helpful PHPUnit assertions:

```php
use App\Factory\PostFactory;

$post = PostFactory::createOne();

$post->assertPersisted();
$post->assertNotPersisted();

$repository = PostFactory::repository();

$repository->assertEmpty();
$repository->assertCount(3);
$repository->assertCountGreaterThan(3);
$repository->assertCountGreaterThanOrEqual(3);
$repository->assertCountLessThan(3);
$repository->assertCountLessThanOrEqual(3);
$repository->assertExists(['title' => 'My Title']);
$repository->assertNotExists(['title' => 'My Title']);
```

### Global State

If you have an initial database state you want for all tests, you can set this in your `tests/bootstrap.php`:

```php
// tests/bootstrap.php
// ...

Zenstruck\Foundry\Test\TestState::addGlobalState(function () {
    CategoryFactory::createOne(['name' => 'php']);
    CategoryFactory::createOne(['name' => 'symfony']);
});
```

To avoid your bootstrap file from becoming too complex, it is best to wrap your global state into a [Story](#stories):

```php
// tests/bootstrap.php
// ...

Zenstruck\Foundry\Test\TestState::addGlobalState(function () {
    GlobalStory::load();
});
```

**NOTES**:
1. You can still access [Story State](#story-state) for *Global State Stories* in your tests and they are still
only loaded once.
2. The [`ResetDatabase`](#enable-foundry-in-your-testcase) trait is required when using global state.

### PHPUnit Data Providers

It is possible to use factories in
[PHPUnit data providers](https://phpunit.readthedocs.io/en/9.3/writing-tests-for-phpunit.html#data-providers):

```php
use App\Factory\PostFactory;

/**
 * @dataProvider postDataProvider
 */
public function test_post_via_data_provider(PostFactory $factory): void
{
    $post = $factory->create();

    // ...
}

public static function postDataProvider(): iterable
{
    yield [PostFactory::new()];
    yield [PostFactory::new()->published()];
}
```

**NOTES**:
1. Be sure your data provider returns only instances of `ModelFactory` and you do not try and call `->create()` on them.
Data providers are computed early in the phpunit process before Foundry is booted.
2. For the same reason as above, it is not possible to use [Factory Services](#factories-as-services) with required
constructor arguments (the container is not yet available).

### Performance

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

3. Pre-encode user passwords with a known value via `bin/console security:encode-password` and set this in
`ModelFactory::getDefaults()`. Add the known value as a `const` on your factory:

    ```php
    class UserFactory extends ModelFactory
    {
        public const DEFAULT_PASSWORD = '1234'; // the password used to create the pre-encoded version below
    
        protected function getDefaults(): array
        {
            return [
                // ...
                'password' => '$argon2id$v=19$m=65536,t=4,p=1$pLFF3D2gnvDmxMuuqH4BrA$3vKfv0cw+6EaNspq9btVAYc+jCOqrmWRstInB2fRPeQ',
            ];
        }
    }
    ```

    Now, in your tests, when you need access to the unencoded password for a user created with `UserFactory`, use
    `UserFactory::DEFAULT_PASSWORD`.

### Non-Kernel Tests

Foundry can be used in standard PHPUnit unit tests (TestCase's that just extend `PHPUnit\Framework\TestCase` and not
`Symfony\Bundle\FrameworkBundle\Test\KernelTestCase`). These tests still require using the `Factories` trait to boot
Foundry but will not have doctrine available. Factories created in these tests will not be persisted (calling
[`->withoutPersisting()`](#without-persisting) is not necessary). Because the bundle is not available in these tests,
any bundle configuration you have will not be picked up. You will need to add
[Test-Only Configuration](#test-only-configuration). Unfortunately, this may mean duplicating your bundle configuration
here.

```php
use App\Factory\PostFactory;
use PHPUnit\Framework\TestCase;
use Zenstruck\Foundry\Test\Factories;

class MyUnitTest extends TestCase
{
    use Factories;

    public function some_test(): void
    {
        $post = PostFactory::createOne();

        // $post is not persisted to the database
    }
}
```

**NOTE**: [Factories as Services](#factories-as-services) and [Stories as Services](#stories-as-services) with required 
constructor arguments are not usable in non-Kernel tests. The container is not available to resolve their dependencies.
The easiest work-around is to make the test an instance of `Symfony\Bundle\FrameworkBundle\Test\KernelTestCase` so the
container is available.

### Test-Only Configuration

Foundry can be configured statically, with pure PHP, in your `tests/bootstrap.php`. This is useful if you have a mix
of Kernel and [non-Kernel tests](#non-kernel-tests) or if using Foundry [without the bundle](#using-without-the-bundle):

```php
// tests/bootstrap.php
// ...

// configure a default instantiator
Zenstruck\Foundry\Test\TestState::setInstantiator(
    (new Zenstruck\Foundry\Instantiator())
        ->withoutConstructor()
        ->allowExtraAttributes()
        ->alwaysForceProperties()
);

// configure a custom faker
Zenstruck\Foundry\Test\TestState::setFaker(Faker\Factory::create('fr_FR'));

// enable auto-refreshing "globally"
Zenstruck\Foundry\Test\TestState::alwaysAutoRefreshProxies();
```

**NOTE**: If using [bundle configuration](#bundle-configuration) as well, *test-only configuration* will override the
bundle configuration.

### Using without the Bundle

The provided bundle is not strictly required to use Foundry for tests. You can have all your factories, stories, and
configuration live in your `tests/` directory. You can configure foundry with
[Test-Only Configuration](#test-only-configuration).

## Stories

Stories are useful if you find your test's *arrange* step is getting complex (loading lots of fixtures) or duplicating
logic between tests and/or your dev fixtures. They are used to extract a specific database *state* into a *story*.
Stories can be loaded in your fixtures and in your tests, they can also depend on other stories.

Create a story using the maker command:

    $ bin/console make:story Post

**NOTE**: Creates `PostStory.php` in `src/Story`, add `--test` flag to create in `tests/Story`.

Modify the *build* method to set the state for this story:

```php
// src/Story/PostStory.php

namespace App\Story;

use App\Factory\CategoryFactory;
use App\Factory\PostFactory;
use App\Factory\TagFactory;
use Zenstruck\Foundry\Story;

final class PostStory extends Story
{
    public function build(): void
    {
        // create 10 Category's
        CategoryFactory::createMany(10);

        // create 20 Tag's
        TagFactory::createMany(20);

        // create 50 Post's
        PostFactory::createMany(50, function() {
            return [
                // each Post will have a random Category (created above)
                'category' => CategoryFactory::random(),

                // each Post will between 0 and 6 Tag's (created above)
                'tags' => TagFactory::randomRange(0, 6),
            ];
        });
    }
}
```

Use the new story in your tests, dev fixtures, or even other stories:

```php
PostStory::load(); // loads the state defined in PostStory::build()

PostStory::load(); // does nothing - already loaded
```

**NOTE**: Objects persisted in stories are cleared after each test (unless it is a
["Global State Story"](#global-state)).

### Stories as Services

If your stories require dependencies, you can define them as a service:

```php
// src/Story/PostStory.php

namespace App\Story;

use App\Factory\PostFactory;
use App\Service\ServiceA;
use App\Service\ServiceB;
use Zenstruck\Foundry\Story;

final class PostStory extends Story
{
    private $serviceA;
    private $serviceB;

    public function __construct(ServiceA $serviceA, ServiceB $serviceB)
    {
        $this->serviceA = $serviceA;
        $this->serviceB = $serviceB;
    }

    public function build(): void
    {
        // can use $this->serviceA, $this->serviceB here to help build this story
    }
}
```

If using a standard Symfony Flex app, this will be autowired/autoconfigured. If not, register the service and tag
with `foundry.story`.

**NOTE:** The provided bundle is required for stories as services.

### Story State

Another feature of *stories* is the ability for them to *remember* the objects they created to be referenced later:

```php
// src/Story/CategoryStory.php

namespace App\Story;

use App\Factory\CategoryFactory;
use Zenstruck\Foundry\Story;

final class CategoryStory extends Story
{
    public function build(): void
    {
        $this->add('php', CategoryFactory::createOne(['name' => 'php']));

        // factories are created when added as state
        $this->add('symfony', CategoryFactory::new(['name' => 'symfony']));
    }
}
```

Later, you can access the story's state when creating other fixtures:

```php
PostFactory::createOne(['category' => CategoryStory::load()->get('php')]);

// or use the magic method (functionally equivalent to above)
PostFactory::createOne(['category' => CategoryStory::php()]);
```

**NOTE**: Story state is cleared after each test (unless it is a ["Global State Story"](#global-state)).

## Bundle Configuration

Since the bundle is intended to be used in your *dev* and *test* environments, you'll want the configuration
for each environment to match. The easiest way to do this is have your *test* config, import *dev*. This
way, there is just one place to set your config.

```yaml
# config/packages/dev/zenstruck_foundry.yaml

zenstruck_foundry:
    # ...
```

```yaml
# config/packages/test/zenstruck_foundry.yaml

# just import the dev config
imports:
    - { resource: ../dev/zenstruck_foundry.yaml }
```

### Full Default Bundle Configuration

```yaml
zenstruck_foundry:

    # Whether to auto-refresh proxies by default (https://github.com/zenstruck/foundry#auto-refresh)
    auto_refresh_proxies: false

    # Configure faker to be used by your factories.
    faker:

        # Change the default faker locale.
        locale:               null # Example: fr_FR

        # Customize the faker service.
        service:              null # Example: my_faker

    # Configure the default instantiator used by your factories.
    instantiator:

        # Whether or not to call an object's constructor during instantiation.
        without_constructor: false

        # Whether or not to allow extra attributes.
        allow_extra_attributes: false

        # Whether or not to skip setters and force set object properties (public/private/protected) directly.
        always_force_properties: false

        # Customize the instantiator service.
        service:              null # Example: my_instantiator
```

<details>
<summary>You can also use PHP syntax. Click to see how.</summary>
<br>

Add this line in your `src/Kernel.php`.
```diff
    protected function configureContainer(ContainerConfigurator $container): void
    {
         $container->import('../config/{packages}/*.yaml');
+        $container->import('../config/{packages}/'.$this->environment.'/*.php');
         $container->import('../config/{packages}/'.$this->environment.'/*.yaml');
        //...
    }
```
And now you are ready to create the `zenstruck_foundry.php` file:
```php
// config/packages/dev/zenstruck_foundry.php

<?php
declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $config): void
{
    $config->extension('zenstruck_foundry', [

        // Whether to auto-refresh proxies by default (https://github.com/zenstruck/foundry#auto-refresh)
        'auto_refresh_proxies' => false,

        // Configure faker to be used by your factories.
        'faker' => [

            // Change the default faker locale.
            'locale' => null,

            // Customize the faker service.
            'service' => null
        ],

        // Configure the default instantiator used by your factories.
        'instantiator' => [

            // Whether or not to call an object's constructor during instantiation.
            'without_constructor' => false,

            // Whether or not to allow extra attributes.
            'allow_extra_attributes' => false,

            // Whether or not to skip setters and force set object properties (public/private/protected) directly.
            'always_force_properties' => false,

            // Customize the instantiator service.
            'service' => null
        ]
    ]);
};
```
</details>


## Credit

The [AAA](https://www.thephilocoder.com/unit-testing-aaa-pattern/) style of testing was first introduced to me by
[Adam Wathan's](https://adamwathan.me/) excellent [Test Driven Laravel Course](https://course.testdrivenlaravel.com/).
The inspiration for this libraries API comes from [Laravel factories](https://laravel.com/docs/master/database-testing)
and [christophrumpel/laravel-factories-reloaded](https://github.com/christophrumpel/laravel-factories-reloaded).
