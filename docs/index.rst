Foundry
=======

Foundry makes creating fixtures data fun again, via an expressive, auto-completable, on-demand fixtures system with
Symfony and Doctrine:

Foundry supports ``doctrine/orm`` (with `doctrine/doctrine-bundle <https://github.com/doctrine/doctrinebundle>`_),
``doctrine/mongodb-odm`` (with `doctrine/mongodb-odm-bundle <https://github.com/doctrine/DoctrineMongoDBBundle>`_)
or a combination of these.

.. admonition:: Screencast
    :class: screencast

    Want to watch a screencast 🎥 about it? Check out `symfonycasts.com/foundry <https://symfonycasts.com/foundry>`_.

.. warning::

    You're reading the documentation for Foundry v2 which is brand new.
    You might want to look at `Foundry v1 documentation <https://symfony.com/bundles/ZenstruckFoundryBundle/1.x/index.html>`_
    or `the upgrade guide to v2 <https://github.com/zenstruck/foundry/blob/1.x/UPGRADE-2.0.md>`_

Installation
------------

.. code-block:: terminal

    $ composer require --dev zenstruck/foundry

To use the ``make:*`` commands from this bundle, ensure
`Symfony MakerBundle <https://symfony.com/bundles/SymfonyMakerBundle/current/index.html>`_ is installed.

*If not using Symfony Flex, be sure to enable the bundle in your **test**/**dev** environments.*

Same Entities used in these Docs
--------------------------------

For the remainder of the documentation, the following sample entities will be used:

::

    namespace App\Entity;

    use App\Repository\CategoryRepository;
    use Doctrine\ORM\Mapping as ORM;

    #[ORM\Entity(repositoryClass: CategoryRepository::class)]
    class Category
    {
        #[ORM\Id]
        #[ORM\GeneratedValue]
        #[ORM\Column(type: 'int')]
        private ?int $id = null;

        public function __construct(
            #[ORM\Column]
            private string $name
        ) {
        }

        // ... getters/setters
    }

::

    namespace App\Entity;

    use App\Repository\PostRepository;
    use Doctrine\ORM\Mapping as ORM;

    #[ORM\Entity(repositoryClass: PostRepository::class)]
    class Post
    {
        #[ORM\Id]
        #[ORM\GeneratedValue]
        #[ORM\Column(type: 'int')]
        private ?int $id = null;

        #[ORM\Column(type: 'text', nullable: true)]
        private ?string $body = null;

        #[ORM\Column(type: 'datetime_immutable')]
        private \DateTimeImmutable $createdAt;

        #[ORM\Column(type: 'datetime_immutable', nullable: true)]
        private ?\DateTimeImmutable $publishedAt = null;

        #[ORM\ManyToOne]
        private ?Category $category = null;

        public function __construct(
            #[ORM\Column]
            private string $title
        )
        {
            $this->title = $title;
            $this->createdAt = new \DateTimeImmutable('now');
        }

        // ... getters/setters
    }

Factories
---------

The nicest way to use Foundry is to generate one *factory* class per ORM entity or MongoDB document.
You can skip this and use `Anonymous Factories`_, but *persistent object factories* give you IDE
auto-completion and access to other useful features.

Generate
~~~~~~~~

Create a persistent object factory for one of your entities with the maker command:

.. code-block:: terminal

    $ php bin/console make:factory

    > Entity class to create a factory for:
    > Post

    created: src/Factory/PostFactory.php

    Next: Open your new factory and set default values/states.

This command will generate a ``PostFactory`` class that looks like this:

::

    // src/Factory/PostFactory.php
    namespace App\Factory;

    use App\Entity\Post;
    use App\Repository\PostRepository;
    use Zenstruck\Foundry\Persistence\PersistentObjectFactory;
    use Zenstruck\Foundry\Persistence\ProxyRepositoryDecorator;

    /**
     * @extends PersistentObjectFactory<Post>
     */
    final class PostFactory extends PersistentObjectFactory
    {
        /**
         * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#factories-as-services
         *
         * @todo inject services if required
         */
        public function __construct()
        {
        }

        public static function class(): string
        {
            return Post::class;
        }

        /**
         * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#factories
         *
         * @todo add your default values here
         */
        protected function defaults(): array|callable
        {
            return [
                'createdAt' => \DateTimeImmutable::createFromMutable(self::faker()->dateTime()),
                'title' => self::faker()->text(255),
            ];
        }

        /**
         * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
         */
        protected function initialize(): static
        {
            return $this
                // ->afterInstantiate(function(Post $post): void {})
            ;
        }
    }

.. tip::

    Using ``make:factory --test`` will generate the factory in ``tests/Factory``.

.. tip::

    When using ``--test`` flag, we're still dealing with ``dev`` environment. And because we want the container to know about our factories,
    we need to declare them as services even if they are in the ``tests`` directory. To do that, add the following to your configuration:

    .. code-block:: yaml

        # config/packages/zenstruck_foundry.yaml
        when@dev:
            services:
                App\Tests\Factory\:
                    resource: '%kernel.project_dir%/tests/Factory/'
                    autowire: true
                    autoconfigure: true

.. tip::

    You can globally configure which namespace the factories will be generated in:

    .. configuration-block::

        .. code-block:: yaml

            # config/packages/zenstruck_foundry.yaml
            zenstruck_foundry:
                make_factory:
                    default_namespace: 'App\MyFactories'

    You can override this configuration by using the ``--namespace`` option.

.. tip::

    Feeling polluted by all these "beginners" hints in the generated factory? You can disable them in the configuration:

    .. configuration-block::

        .. code-block:: yaml

            # config/packages/zenstruck_foundry.yaml
            zenstruck_foundry:
                make_factory:
                    add_hints: false

.. _defaults:

In the ``defaults()``, you can return an array of all default values that any new object
should have. `Faker`_ is available to easily get random data:

::

    protected function defaults(): array
    {
        return [
            // use the built-in Faker integration to generate good random values...
            'title' => self::faker()->unique()->sentence(),
            'body' => self::faker()->sentence(),

            // ...or generate the values yourself if you prefer
            'createdAt' => new \DateTimeImmutable('today'),
        ];
    }

These default values are applied to both the **constructor arguments** and the
**properties** of the objects. For example, defining a default value for ``title``
will first attempt to set a constructor argument called ``$title``. If that doesn't
exist, the `PropertyAccess <https://symfony.com/doc/current/components/property_access.html>`_
component will be used to call the ``setTitle()`` method or directly set the public
``$title`` property. More about this in the :ref:`instantiation and hydration <instantiation>` section.

.. tip::

    It is best to have ``defaults()`` return the attributes to persist a valid object
    (all non-nullable fields).

.. tip::

    Using ``make:factory --all-fields`` will generate default values for all fields of the entity,
    not only non-nullable fields.

.. note::

    ``defaults()`` is called everytime a factory is instantiated (even if you don't end up
    creating it). `Lazy Values`_ allows you to ensure the value is only calculated when/if it's needed.

Using your Factory
~~~~~~~~~~~~~~~~~~

::

    use App\Factory\PostFactory;

    // create/persist Post with random data from `defaults()`
    PostFactory::createOne();

    // or provide values for some properties (others will be random)
    PostFactory::createOne(['title' => 'My Title']);

    // createOne() returns the persisted Post object wrapped in a Proxy object
    $post = PostFactory::createOne();

    // create/persist 5 Posts with random data from defaults()
    PostFactory::createMany(5); // returns Post[]
    PostFactory::createMany(5, ['title' => 'My Title']);

    // Create 5 posts with incremental title
    PostFactory::createMany(
        5,
        static function(int $i) {
            return ['title' => "Title $i"]; // "Title 1", "Title 2", ... "Title 5"
        }
    );

    // find a persisted object for the given attributes, if not found, create with the attributes
    PostFactory::findOrCreate(['title' => 'My Title']); // returns Post

    PostFactory::first(); // get the first object (assumes an auto-incremented "id" column)
    PostFactory::first('createdAt'); // assuming "createdAt" is a datetime column, this will return latest object
    PostFactory::last(); // get the last object (assumes an auto-incremented "id" column)
    PostFactory::last('createdAt'); // assuming "createdAt" is a datetime column, this will return oldest object

    PostFactory::truncate(); // empty the database table

    PostFactory::count(); // the number of persisted Posts
    PostFactory::count(['category' => $category]); // the number of persisted Posts with the given category

    PostFactory::all(); // Post[] all the persisted Posts

    PostFactory::findBy(['author' => 'kevin']); // Post[] matching the filter

    $post = PostFactory::find(5); // Post with the id of 5
    $post = PostFactory::find(['title' => 'My First Post']); // Post matching the filter

    // get a random object that has been persisted
    $post = PostFactory::random(); // returns Post
    $post = PostFactory::random(['author' => 'kevin']); // filter by the passed attributes

    // or automatically persist a new random object if none exists
    $post = PostFactory::randomOrCreate();
    $post = PostFactory::randomOrCreate(['author' => 'kevin']); // filter by or create with the passed attributes

    // get a random set of objects that have been persisted
    $posts = PostFactory::randomSet(4); // array containing 4 "Post" objects
    $posts = PostFactory::randomSet(4, ['author' => 'kevin']); // filter by the passed attributes

    // random range of persisted objects
    $posts = PostFactory::randomRange(0, 5); // array containing 0-5 "Post" objects
    $posts = PostFactory::randomRange(0, 5, ['author' => 'kevin']); // filter by the passed attributes

    // or automatically persist a new random range of objects if none exists
    $posts = PostFactory::randomRangeOrCreate(0, 5); // array containing 0-5 "Post" objects
    $posts = PostFactory::randomRangeOrCreate(0, 5, ['author' => 'kevin']); // filter by or create with the passed attributes

Reusable Factory "States"
~~~~~~~~~~~~~~~~~~~~~~~~~

You can add any methods you want to your factories (i.e. static methods that create an object in a certain way) but
you can also add *states*:

::

    final class PostFactory extends PersistentObjectFactory
    {
        // ...

        public function published(): self
        {
            // call setPublishedAt() and pass a random DateTime
            return $this->with(['published_at' => self::faker()->dateTime()]);
        }

        public function unpublished(): self
        {
            return $this->with(['published_at' => null]);
        }

        public function withViewCount(?int $count = null): self
        {
            return $this->with(function () use ($count) {
                return ['view_count' => $count ?? self::faker()->numberBetween(0, 10000)];
            });
        }
    }

You can use states to make your tests very explicit to improve readability:

::

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

.. note::

    Be sure to chain the states/hooks off of ``$this`` because factories are `Immutable`_.

Attributes
~~~~~~~~~~

The attributes used to instantiate the object can be added several ways. Attributes can be an *array*, or a *callable*
that returns an array. Using a *callable* ensures random data as the callable is run for each object separately during
instantiation.

::

    use App\Entity\Category;
    use App\Entity\Post;
    use App\Factory\CategoryFactory;
    use App\Factory\PostFactory;
    use function Zenstruck\Foundry\faker;

    // The first argument to "new()" allows you to overwrite the default
    // values that are defined in the `PostFactory::defaults()`
    $posts = PostFactory::new(['title' => 'Post A'])
        ->with([
            'body' => 'Post Body...',

            // CategoryFactory will be used to create a new Category for each Post
            'category' => CategoryFactory::new(['name' => 'php']),
        ])
        ->with([
            // Proxies are automatically converted to their wrapped object
            // will override previous category
            'category' => CategoryFactory::createOne(['name' => 'Symfony']),
        ])
        ->with(function() { return ['createdAt' => faker()->dateTime()]; }) // see faker section below

        // create "2" Post's
        ->many(2)->create(['title' => 'Different Title'])
    ;

    $posts[0]->getTitle(); // "Different Title"
    $posts[0]->getBody(); // "Post Body..."
    $posts[0]->getCategory(); // Category with name "Symfony"
    $posts[0]->getPublishedAt(); // \DateTime('last week')
    $posts[0]->getCreatedAt(); // random \DateTime

    $posts[1]->getTitle(); // "Different Title"
    $posts[1]->getBody(); // "Post Body..."
    $posts[1]->getCategory(); // Category with name "Symfony" (same object than above)
    $posts[1]->getPublishedAt(); // \DateTime('last week')
    $posts[1]->getCreatedAt(); // random \DateTime (different than above)

.. note::

    Attributes passed to the ``create*`` methods are merged with any attributes set via ``defaults()``
    and ``with()``.

Sequences
~~~~~~~~~

Sequences help to create different objects in one call:

::

    use App\Factory\PostFactory;

    // create/persist 2 posts based on a sequence of attributes
    PostFactory::createSequence(
        [
            ['name' => 'title 1'],
            ['name' => 'title 2'],
        ]
    );

    // create 10 posts using a sequence callback with an incremental index
    PostFactory::createSequence(
        function() {
            foreach (range(1, 10) as $i) {
                yield ['name' => "title $i"];
            }
        }
    );

    // sequences could also be used with a factory with states
    $posts = PostFactory::new()
        ->unpublished()
        ->sequence(
            [
                ['name' => 'title 1'],
                ['name' => 'title 2'],
            ]
        )->create();

Distribute values over a collection
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

If you have a collection of values that you want to distribute over a collection, you can use the ``distribute()`` method:

::

    // let's say we have 2 categories...
    $categories = CategoryFactory::createSequence(
        [
            ['name' => 'category 1'],
            ['name' => 'category 2'],
        ]
    );

    // ...that we want to "distribute" over 2 posts
    $posts = PostFactory::new()
        ->sequence(
            [
                ['name' => 'post 1'],
                ['name' => 'post 2'],
            ]
        )

        // "post 1" will have "category 1" and "post 2" will have "category 2"
        ->distribute('category', $categories)

        // you can even chain "distribute()" methods:
        // first post is published today, second post is published tomorrow
        ->distribute('publishedAt', [new \DateTimeImmutable('today'), new \DateTimeImmutable('tomorrow')])

        ->create();

.. versionadded::  2.4

    The ``distribute()`` method was added in Foundry 2.4.

Apply State Method over a Collection
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

It is also possible to call a "state method" on each element of a collection by using the method ``FactoryCollection::applyStateMethod()``
(after a ``many()``, a ``sequence()``, or a ``distribute()``):

::

    final class PostFactory extends PersistentObjectFactory
    {
        // ...

        public function published(): self
        {
            return $this->with(['published_at' => self::faker()->dateTime()]);
        }

        public function title(string $title): self
        {
            return $this->with(['title' => $title]);
        }
    }

    $post = PostFactory::new()
        ->many(3)
        ->applyStateMethod('published')
        ->applyStateMethod('title', static fn(int $i) => ["title $i"])
        ->create()
    ;

Faker
~~~~~

This library provides a wrapper for `FakerPHP <https://fakerphp.org/>`_ to help with generating
random data for your factories:

::

    use function Zenstruck\Foundry\faker;

    faker()->email(); // random email

.. note::

    You can customize Faker's `locale <https://fakerphp.org/#localization>`_:

    .. code-block:: yaml

        # config/packages/zenstruck_foundry.yaml
        when@dev: # see Bundle Configuration section about sharing this in the test environment
            zenstruck_foundry:
                faker:
                    locale: fr_FR # set the locale

.. note::

    You can register your own *Faker Provider* by tagging any service with ``foundry.faker_provider``.
    All public methods on this service will be available on Foundry's Faker instance::

        use function Zenstruck\Foundry\faker;

        faker()->customMethodOnMyService();

.. note::

    For full control, you can register your own ``Faker\Generator`` service:

    .. code-block:: yaml

        # config/packages/zenstruck_foundry.yaml
        when@dev: # see Bundle Configuration section about sharing this in the test environment
            zenstruck_foundry:
                faker:
                    service: my_faker # service id for your own instance of Faker\Generator

Reproducibility
...............

Foundry sets a different random seed for each PHPUnit run. This means that Faker will generate different data on each run.
If you're using Foundry's `PHPUnit Extension`_, it will automatically display the seed used for each test run.

You can also freeze the seed, by using the environment variable ``FOUNDRY_FAKER_SEED``:

.. code-block:: terminal

    $ FOUNDRY_FAKER_SEED=1234 vendor/bin/phpunit

    ...................................                               35 / 35 (100%)

    Faker seed: 1234

    Time: 00:00.047, Memory: 48.50 MB

.. versionadded::  2.4

    Support for ``FOUNDRY_FAKER_SEED`` was added in 2.4.


Hooks
~~~~~

The following hooks can be added to factories. Multiple hooks callbacks can be added, they are run in the order
they were added.

::

    use App\Factory\PostFactory;
    use Zenstruck\Foundry\Proxy;

    PostFactory::new()
        ->beforeInstantiate(function(array $parameters, string $class, static $factory): array {
            // $parameters is what will be used to instantiate the object, manipulate as required
            // $class is the class of the object being instantiated
            // $factory is the factory instance which creates the object
            $parameters['title'] = 'Different title';

            return $parameters; // must return the final $parameters
        })
        ->afterInstantiate(function(Post $object, array $parameters, static $factory): void {
            // $object is the instantiated object
            // $parameters contains the attributes used to instantiate the object and any extras
            // $factory is the factory instance which creates the object
        })
        ->afterPersist(function(Post $object, array $parameters, static $factory) {
            // this event is only called if the object was persisted
            // $object is the persisted Post object
            // $parameters contains the attributes used to instantiate the object and any extras
            // $factory is the factory instance which creates the object
        })

        // multiple events are allowed
        ->beforeInstantiate(function($parameters) { return $parameters; })
        ->afterInstantiate(function() {})
        ->afterPersist(function() {})
    ;

You can also add hooks directly in your factory class:

::

    protected function initialize(): static
    {
        return $this
            ->afterPersist(function() {})
        ;
    }

Read `Initialization`_ to learn more about the ``initialize()`` method.

Initialization
~~~~~~~~~~~~~~

You can override your factory's ``initialize()`` method to add default state/logic:

::

    final class PostFactory extends PersistentObjectFactory
    {
        // ...

        protected function initialize(): static
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

.. _instantiation:

Object Instantiation & Hydration
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

By default, objects are instantiated in the normal fashion, by using the object's constructor. Attributes
that match constructor arguments are used. Remaining attributes are used in the hydration phase and set to the object
using Symfony's `PropertyAccess <https://symfony.com/doc/current/components/property_access.html>`_ component
(setters/public properties). Any extra attributes cause an exception to be thrown.

You can customize the instantiator in several ways, so that Foundry will instantiate and hydrate your objects, using the
attributes provided:

::

    use App\Entity\Post;
    use App\Factory\PostFactory;
    use Zenstruck\Foundry\Object\Instantiator;

    // set the instantiator for the current factory
    PostFactory::new()
        // instantiate the object without calling the constructor
        ->instantiateWith(Instantiator::withoutConstructor())

        // "foo" and "bar" attributes are ignored when instantiating
        ->instantiateWith(Instantiator::withConstructor()->allowExtra('foo', 'bar'))

        // all extra attributes are ignored when instantiating
        ->instantiateWith(Instantiator::withConstructor()->allowExtra())

        // force set "title" and "body" when instantiating
        ->instantiateWith(Instantiator::withConstructor()->alwaysForce(['title', 'body']))

        // never use setters, always "force set" properties (even private/protected, does not use setter)
        ->instantiateWith(Instantiator::withConstructor()->alwaysForce())

        // can combine the different "modes"
        ->instantiateWith(Instantiator::withoutConstructor()->allowExtra()->alwaysForce())

        // use a "namedConstructor"
        ->instantiateWith(Instantiator::namedConstructor("methodName"))

        // use a callable: it will be passed the attributes matching its parameters names,
        // remaining attributes will be used in the hydration phase
        ->instantiateWith(Instantiator::use(function(string $title): object {
            return new Post($title); // ... your own instantiation logic
        }))
    ;

If this does not suit your needs, the instantiator is just a callable. You can provide your own to have complete control
over instantiation and hydration phases:

::

        ->instantiateWith(function(array $attributes, string $class): object {
            return new Post(); // ... your own logic
        })

.. warning::

    The ``instantiateWith(callable(...))`` method fully replaces the default instantiation
    and object hydration system. Attributes defined in the ``defaults()`` method,
    as well as any states defined with the ``with()`` method, **will not be
    applied automatically**. However, they are available as arguments to the
    ``instantiateWith()`` callable.

You can customize the instantiator globally for all your factories (can still be overruled by factory instance
instantiators):

.. code-block:: yaml

    # config/packages/zenstruck_foundry.yaml
    when@dev: # see Bundle Configuration section about sharing this in the test environment
        zenstruck_foundry:
            instantiator:
                use_constructor: false # always instantiate objects without calling the constructor
                allow_extra_attributes: true # always ignore extra attributes
                always_force_properties: true # always "force set" properties
                # or
                service: my_instantiator # your own invokable service for complete control

.. _force-helper:

``force()`` helper
..................

``Instantiator::alwaysForce()`` forces the property globally for the factory.

It is also possible to force a property on-demand, thanks to the ``force()`` helper. You can use it to temporary
prevent using a setter (constructor arguments will still be passed)

::

    use App\Factory\PostFactory;

    use function Zenstruck\Foundry\force;

    // in this case, the "body" attribute will be set directly, without using the setter
    PostFactory::createOne(['body' => force('some body')]) ;

    // in this case, the "title" attribute will still be used in the constructor (otherwise an error would be thrown)
    PostFactory::createOne(['title' => force('some title')]) ;
    // ...unless we disable the constructor:
    PostFactory::new()
        ->instantiateWith(Instantiator::withoutConstructor())
        ->create(['title' => force('some title')]) ;

Immutable
~~~~~~~~~

Factories are immutable:

::

    use App\Factory\PostFactory;

    $factory = PostFactory::new();
    $factory1 = $factory->with([]); // returns a new PostFactory object
    $factory2 = $factory->instantiateWith(function () {}); // returns a new PostFactory object
    $factory3 = $factory->beforeInstantiate(function () {}); // returns a new PostFactory object
    $factory4 = $factory->afterInstantiate(function () {}); // returns a new PostFactory object
    $factory5 = $factory->afterPersist(function () {}); // returns a new PostFactory object

Doctrine Relationships
~~~~~~~~~~~~~~~~~~~~~~

Assuming your entities follow the
`best practices for Doctrine Relationships <https://symfony.com/doc/current/doctrine/associations.html>`_ and you are
using the :ref:`default instantiator <instantiation>`, Foundry *just works* with doctrine relationships. There are some
nuances with the different relationships and how entities are created. The following tries to document these for
each relationship type.

Many-to-One
...........

The following assumes the ``Comment`` entity has a many-to-one relationship with ``Post``:

::

    use App\Factory\CommentFactory;
    use App\Factory\PostFactory;

    // Example 1: pre-create Post and attach to Comment
    $post = PostFactory::createOne();

    CommentFactory::createOne(['post' => $post]);

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

.. tip::

    It is recommended that the only relationship you define in ``defaults()`` is non-null
    Many-to-One's.

.. tip::

    It is also recommended that your ``defaults()`` return a ``Factory`` and not the created entity.
    However, you can use `Lazy Values`_ if you need to create the entity in the ``defaults()`` method.

::

        protected function defaults(): array
        {
            return [
                // RECOMMENDED
                // The Post will only be created when the factory is instantiated
                'post' => PostFactory::new(),
                'post' => PostFactory::new()->published(),
                // The callback will be called when the factory is instantiated, creating the Post
                'post' => LazyValue::new(fn () => PostFactory::createOne()),
                'post' => lazy(fn () => PostFactory::new()->published()->create()),

                // NOT RECOMMENDED
                // Will potentially result in extra unintended Posts (if you override the value during instantiation)
                'post' => PostFactory::createOne(),
                'post' => PostFactory::new()->published()->create(),
            ];
        }

One-to-Many
...........

The following assumes the ``Post`` entity has a one-to-many relationship with ``Comment``:

::

    use App\Factory\CommentFactory;
    use App\Factory\PostFactory;

    // Example 1: Create a Post with 6 Comments
    PostFactory::createOne(['comments' => CommentFactory::new()->many(6)]);

    // Example 2: Create 6 Posts each with 4 Comments (24 Comments total)
    PostFactory::createMany(6, ['comments' => CommentFactory::new()->many(4)]);

    // Example 3: Create 6 Posts each with between 0 and 10 Comments
    PostFactory::createMany(6, ['comments' => CommentFactory::new()->range(0, 10)]);

Many-to-Many
............

The following assumes the ``Post`` entity has a many-to-many relationship with ``Tag``:

::

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

Reuse Objects in Relationships
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

When creating nested objects, sometimes it can be useful to tell Foundry to always use the same object for a given class.
It can enforce coherence in your fixtures and avoid creating too many objects.

In order to do this, you can use the ``reuse()`` method: it will force Foundry to use the object passed as parameter in
all ``ManyToOne`` and ``OneToOne`` relationships using the class of this object:

::

    // let's say both Post and Comment classes have a ManyToOne field "author" of class User
    $user = UserFactory::createOne();

    PostFactory::new([
        'comments' => CommentFactory::new()->many(5),
    ])
        // by calling reuse, the post and all its comments will have the same author
        ->reuse($user)
        ->create();

.. versionadded::  2.4

    The ``reuse()`` method was added in Foundry 2.4.

Lazy Values
~~~~~~~~~~~

The ``defaults()`` method is called everytime a factory is instantiated (even if you don't end up
creating it). Sometimes, you might not want your value calculated every time. For example, if you have a value for one
of your attributes that:

* has side effects (i.e. creating a file or fetching a random existing entity from another factory)
* you only want to calculate once (i.e. creating an entity from another factory to pass as a value into multiple other factories)

You can wrap the value in a ``LazyValue`` which ensures the value is only calculated when/if it's needed. Additionally,
the LazyValue can be `memoized <https://en.wikipedia.org/wiki/Memoization>`_ so that it is only calculated once.

::

        use Zenstruck\Foundry\LazyValue;

        class TaskFactory extends PersistentObjectFactory
        {
            // ...

            protected function defaults(): array
            {
                $owner = LazyValue::memoize(fn() => UserFactory::createOne());

                return [
                    // Call CategoryFactory::random() everytime this factory is instantiated
                    'category' => LazyValue::new(fn() => CategoryFactory::random()),
                    // The same User instance will be both added to the Project and set as the Task owner
                    'project' => ProjectFactory::new(['users' => [$owner]]),
                    'owner'   => $owner,
                ];
            }
        }

.. tip::

    the ``lazy()`` and ``memoize()`` helper functions can also be used to create LazyValues,
    instead of ``LazyValue::new()`` and ``LazyValue::memoize()``.

Factories as Services
~~~~~~~~~~~~~~~~~~~~~

If your factories require dependencies, you can define them as a service. The following example demonstrates a very
common use-case: encoding a password with the ``UserPasswordHasherInterface`` service.

::

    // src/Factory/UserFactory.php
    use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

    final class UserFactory extends PersistentObjectFactory
    {
        // the injected service should be nullable in order to be used in unit test, without container
        public function __construct(
            private ?UserPasswordHasherInterface $passwordHasher = null
        ) {
            parent::__construct();
        }

        public static function class(): string
        {
            return User::class;
        }

        protected function defaults(): array
        {
            return [
                'email' => self::faker()->unique()->safeEmail(),
                'password' => '1234',
            ];
        }

        protected function initialize(): static
        {
            return $this
                ->afterInstantiate(function(User $user) {
                    if ($this->passwordHasher !== null) {
                        $user->setPassword($this->passwordHasher->hashPassword($user, $user->getPassword()));
                    }
                })
            ;
        }
    }

If using a standard Symfony Flex app, this will be autowired/autoconfigured. If not, register the service and tag
with ``foundry.factory``.

Use the factory as normal:

::

    UserFactory::createOne(['password' => 'mypass'])->getPassword(); // "mypass" encoded
    UserFactory::createOne()->getPassword(); // "1234" encoded (because "1234" is set as the default password)

.. note::

    The provided bundle is required for factories as services.

.. note::

    If using ``make:factory --test``, factories will be created in the ``tests/Factory`` directory which is not
    autowired/autoconfigured in a standard Symfony Flex app. You will have to manually register these as
    services.

.. warning::

    "Service factories" are meant to be used along with "functional" or "integration" tests (the ones using ``KernelTestCase``
    or ``WebTestCase``). If you want to use them in "unit tests" (the ones using ``TestCase``), where Symfony's container
    cannot be used, you will have to make the injected services nullable.

Anonymous Factories
~~~~~~~~~~~~~~~~~~~

Foundry can be used to create factories for entities that you don't have factories for:

::

    use App\Entity\Post;
    use function Zenstruck\Foundry\Persistence\persistent_factory;
    use function Zenstruck\Foundry\Persistence\repository;

    $factory = persistent_factory(Post::class);

    // has the same API as non-anonymous factories
    $factory->create(['field' => 'value']);
    $factory->many(5)->create(['field' => 'value']);
    $factory->instantiateWith(function () {});
    $factory->beforeInstantiate(function () {});
    $factory->afterInstantiate(function () {});
    $factory->afterPersist(function () {});

    // in order to access stored data, use `repository()` helper:
    $repository = repository(Post::class);

    $repository->first(); // get the first object (assumes an auto-incremented "id" column)
    $repository->first('createdAt'); // assuming "createdAt" is a datetime column, this will return latest object
    $repository->last(); // get the last object (assumes an auto-incremented "id" column)
    $repository->last('createdAt'); // assuming "createdAt" is a datetime column, this will return oldest object

    $repository->truncate(); // empty the database table
    $repository->count(); // the number of persisted Post's
    $repository->all(); // Post[] all the persisted Post's

    $repository->findBy(['author' => 'kevin']); // Post[] matching the filter

    $repository->find(5); // Post with the id of 5
    $repository->find(['title' => 'My First Post']); // Post matching the filter

    // get a random object that has been persisted
    $repository->random(); // returns Post
    $repository->random(['author' => 'kevin']); // filter by the passed attributes

    // get a random set of objects that have been persisted
    $repository->randomSet(4); // array containing 4 "Post" objects
    $repository->randomSet(4, ['author' => 'kevin']); // filter by the passed attributes

    // random range of persisted objects
    $repository->randomRange(0, 5); // array containing 0-5 "Post" objects
    $repository->randomRange(0, 5, ['author' => 'kevin']); // filter by the passed attributes

.. note::

    If your anonymous factory code is getting too complex, this could be a sign you need an explicit factory class.

Delay Flush
~~~~~~~~~~~

When creating/persisting many factories at once, it can improve performance
to instantiate them all without saving to the database, then flush them all at
once. To do this, wrap the operations in a ``flush_after()`` callback:

::

    use function Zenstruck\Foundry\Persistence\flush_after;

    flush_after(function() {
        CategoryFactory::createMany(100); // instantiated/persisted but not flushed
        TagFactory::createMany(200); // instantiated/persisted but not flushed
    }); // single flush

The ``flush_after()`` function forwards the callback's return, in case you need to use the objects in your tests:

::

    use function Zenstruck\Foundry\Persistence\flush_after;

    [$category, $tag] = flush_after(fn() => [
        CategoryFactory::createOne(),
        TagFactory::createOne(),
    ]);

Flush once
~~~~~~~~~~

Foundry used to call ``ObjectManager::flush()`` for every entity (or ODM document) created. This could have a performance
downside. Since 2.5, Foundry is able to only call ``flush()`` once per call of ``PersistentObjectFactory::create()`` in
userland. "Flush once" can change some behaviors in a subtle way, so it can be enabled by configuration. Not enabling it
is deprecated, but you can migrate at your own pace.

.. configuration-block::

    .. code-block:: yaml

        zenstruck_foundry:
            persistence:
                flush_once: true

.. versionadded::  2.5

    Flush once capability was introduced in Foundry 2.5.


Not-persisted objects factory
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

When dealing with objects which are not aimed to be persisted, you can make your factory inherit from
``Zenstruck\Foundry\ObjectFactory``. This will create plain objects, that does not interact with database.

.. _without-persisting:

Without Persisting
~~~~~~~~~~~~~~~~~~

"Persistent factories" can also create objects without persisting them. This can be useful for unit tests where you just
want to test the behavior of the actual object or for creating objects that are not entities.

::

    use App\Entity\Post;
    use App\Factory\PostFactory;
    use function Zenstruck\Foundry\object;
    use function Zenstruck\Foundry\Persistence\persistent_factory;
    use function Zenstruck\Foundry\Persistence\save;

    $post = PostFactory::new()->withoutPersisting()->create(); // returns Post
    $post->setTitle('something else'); // do something with object
    save($post); // persist the Post

    $posts = PostFactory::new()->withoutPersisting()->many(5)->create(); // returns Post[]

    // anonymous factories:
    $factory = persistent_factory(Post::class);

    $entity = $factory->withoutPersisting()->create(['field' => 'value']); // returns Post

    $entities = $factory->withoutPersisting()->many(5)->create(['field' => 'value']); // returns Post[]|Proxy[]

    // convenience functions
    $entity = object(Post::class, ['field' => 'value']);

If you'd like your factory to not persist by default, override its ``initialize()`` method to add this behavior:

::

    protected function initialize(): static
    {
        return $this
            ->withoutPersisting()
        ;
    }

Now, after creating objects using this factory, you'd have to call ``\Zenstruck\Foundry\Persistence\save()`` to actually
persist them to the database.

Array factories
~~~~~~~~~~~~~~~

You can even create associative arrays, with the nice DX provided by Foundry:

::

    use Zenstruck\Foundry\ArrayFactory;

    final class SomeArrayFactory extends ArrayFactory
    {
        protected function defaults(): array|callable
        {
            return [
                'prop1' => 'default value 1',
                'prop2' => 'default value 2',
            ];
        }
    }

    // somewhere in a test

    // will create ['prop1' => 'foo', 'prop2' => 'default value 2']
    $array = SomeArrayFactory::createOne(['prop1' => 'foo']);

Helper Functions
~~~~~~~~~~~~~~~~

Foundry provides some helper functions:

Objects related helpers
.......................

- ``Zenstruck\Foundry\factory(string $class, array|callable $attributes = []): ObjectFactory``: Creates an "anonymous" factory, with default attributes
- ``Zenstruck\Foundry\object(string $class, array|callable $attributes = []): object``: Directly creates an object, based on the attributes provided
- ``Zenstruck\Foundry\set(object $object, string $property, mixed $value): object``: Forces set a property for an object (uses reflection)
- ``Zenstruck\Foundry\get(object $object, string $property): mixed``: Gets the value of a property
- ``Zenstruck\Foundry\lazy(): LazyValue``: see `Lazy Values`_
- ``Zenstruck\Foundry\memoize(): LazyValue``: see `Lazy Values`_
- ``Zenstruck\Foundry\force(): ForceValue``: see :ref:`force() helper <force-helper>`

Persistence related helpers
...........................

- ``Zenstruck\Foundry\Persistent\repository(string $class): RepositoryDecorator``: Returns a ``RepositoryDecorator`` for the given class
- ``Zenstruck\Foundry\Persistent\persistent_factory(string $class, array|callable $attributes = []): PersistentObjectFactory``: Creates an "anonymous" persistent factory, with default attributes
- ``Zenstruck\Foundry\Persistent\persist(string $class, array|callable $attributes = []): object``: Directly creates an object (and persist it), based on the attributes provided
- ``Zenstruck\Foundry\Persistent\save(object $object): object``: Saves to the database the given object
- ``Zenstruck\Foundry\Persistent\refresh(object &$object): object``: Refresh the object from the database
- ``Zenstruck\Foundry\Persistent\refresh_all(): void``: Refresh all persisted objects created by Foundry for which a reference exists (PHP 8.4 only)
- ``Zenstruck\Foundry\Persistent\delete(object $object): object``: Removes an object from the database
- ``Zenstruck\Foundry\Persistent\flush_after(callable $callback): mixed``: see `Delay Flush`_
- ``Zenstruck\Foundry\Persistent\disable_persisting(): void``: Disable the persistence of the factories for the current test
- ``Zenstruck\Foundry\Persistent\enable_persisting(): void``: Re-enable the persistence of the factories
- ``Zenstruck\Foundry\Persistent\assert_persisted(object $object, string $message = '{entity} is not persisted.'): object``: see `Assertions`_
- ``Zenstruck\Foundry\Persistent\assert_not_persisted(object $object, string $message = '{entity} is persisted.'): object``: see `Assertions`_

Stories
-------

Stories are useful if you find your test's *arrange* step is getting complex (loading lots of fixtures) or duplicating
logic between tests and/or your dev fixtures. They are used to extract a specific database *state* into a *story*.
Stories can be loaded in your fixtures and in your tests, they can also depend on other stories.

Create a story using the maker command:

.. code-block:: terminal

    $ php bin/console make:story Post

.. note::

    Creates ``PostStory.php`` in ``src/Story``, add ``--test`` flag to create in ``tests/Story``.

Modify the *build* method to set the state for this story:

::

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

Use the new story in your tests, dev fixtures, or even other stories:

::

    PostStory::load(); // loads the state defined in PostStory::build()

    PostStory::load(); // does nothing - already loaded

.. note::

    Objects persisted in stories are cleared after each test (unless it is a
    :ref:`Global State Story <global-state>`).

Stories as Services
~~~~~~~~~~~~~~~~~~~

If your stories require dependencies, you can define them as a service:

::

    // src/Story/PostStory.php
    namespace App\Story;

    use App\Factory\PostFactory;
    use App\Service\MyService;
    use Zenstruck\Foundry\Story;

    final class PostStory extends Story
    {
        public function __construct(
            private MyService $myService,
        ) {
        }

        public function build(): void
        {
            // $this->myService can be used here to help build this story
        }
    }

If using a standard Symfony Flex app, this will be autowired/autoconfigured. If not, register the service and tag
with ``foundry.story``.

Story State
~~~~~~~~~~~

Another feature of *stories* is the ability for them to *remember* the objects they created to be referenced later:

::

    // src/Story/CategoryStory.php
    namespace App\Story;

    use App\Factory\CategoryFactory;
    use Zenstruck\Foundry\Story;

    final class CategoryStory extends Story
    {
        public function build(): void
        {
            $this->addState('php', CategoryFactory::createOne(['name' => 'php']));

            // factories are created when added as state
            $this->addState('symfony', CategoryFactory::new(['name' => 'symfony']));
        }
    }

Later, you can access the story's state when creating other fixtures:

::

    PostFactory::createOne(['category' => CategoryStory::get('php')]);

    // or use the magic method (functionally equivalent to above)
    PostFactory::createOne(['category' => CategoryStory::php()]);

.. tip::

    Unlike factories, stories are not tied to a specific type, and then they cannot be generic, but you can leverage
    the magic method and PHPDoc to improve autocompletion and fix static analysis issues with stories:

    ::

        // src/Story/CategoryStory.php
        namespace App\Story;

        use App\Factory\CategoryFactory;
        use Zenstruck\Foundry\Story;

        /**
         * @method static Category<Category> php()
         */
        final class CategoryStory extends Story
        {
            public function build(): void
            {
                $this->addState('php', CategoryFactory::createOne(['name' => 'php']));
            }
        }

    Now your IDE will know ``CategoryStory::php()`` returns an object of type ``Category``.

    Using a magic method also does not require a prior ``::load()`` call on the story, it will initialize itself.

.. note::

    Story state is cleared after each test (unless it is a :ref:`Global State Story <global-state>`).

Story Pools
~~~~~~~~~~~

Stories can store (as state) *pools* of objects:

::

    // src/Story/ProvinceStory.php
    namespace App\Story;

    use App\Factory\ProvinceFactory;
    use Zenstruck\Foundry\Story;

    final class ProvinceStory extends Story
    {
        public function build(): void
        {
            // add collection to a "pool"
            $this->addToPool('be', ProvinceFactory::createMany(5, ['country' => 'BE']));

            // equivalent to above
            $this->addToPool('be', ProvinceFactory::new(['country' => 'BE'])->many(5));

            // add single object to a pool
            $this->addToPool('be', ProvinceFactory::createOne(['country' => 'BE']));

            // add single object to single pool and make available as "state"
            $this->addState('be-1', ProvinceFactory::createOne(['country' => 'BE']), 'be');
        }
    }

Objects can be fetched from pools in your tests, fixtures or other stories:

::

    ProvinceStory::getRandom('be'); // random Province from "be" pool
    ProvinceStory::getRandomSet('be', 3); // 3 random Province from "be" pool
    ProvinceStory::getRandomRange('be', 1, 4); // between 1 and 4 random Province from "be" pool
    ProvinceStory::getPool('be'); // all Province from "be" pool

#[WithStory] Attribute
~~~~~~~~~~~~~~~~~~~~~~

.. versionadded:: 2.3

    The ``#[WithStory]`` attribute was added in Foundry 2.3.

.. warning::

    The `PHPUnit Extension`_ for Foundry is needed to use ``#[WithStory]`` attribute.

You can use the ``#[WithStory]`` attribute to load stories in your tests:

::

    use App\Story\CategoryStory;
    use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
    use Zenstruck\Foundry\Attribute\WithStory;

    // You can use the attribute on the class...
    #[WithStory(CategoryStory::class)]
    final class NeedsCategoriesTest extends KernelTestCase
    {
        // ... or on the method
        #[WithStory(CategoryStory::class)]
        public function testThatNeedStories(): void
        {
            // ...
        }
    }

If used on the class, the story will be loaded before each test method.

Local Development Fixtures
--------------------------

.. versionadded:: 2.6

    The ``foundry:load-fixtures`` command and ``#[AsFixture]`` attribute were added in 2.6.

Using ``bin/console foundry:load-fixtures``, you can load stories as fixtures in your database.
This is mainly useful to load fixtures in "dev" mode.

Mark `Stories`_ you want loaded by the command with the ``#[AsFixture]`` attribute:

::

    use Zenstruck\Foundry\Attribute\AsFixture;

    #[AsFixture(name: 'category')]
    final class CategoryStory extends Story
    {
        // ...
    }

``bin/console foundry:load-fixtures category`` will now load the story ``CategoryStory`` in your database.

.. note::

    If only a single story exists, you can omit the argument and just call ``bin/console foundry:load-fixtures`` to load it.

You can also load stories by group, by using the ``groups`` option:

::

    use Zenstruck\Foundry\Attribute\AsFixture;

    #[AsFixture(name: 'category', groups: ['all'])]
    final class CategoryStory extends Story {}

    #[AsFixture(name: 'post', groups: ['all'])]
    final class PostStory extends Story {}

``bin/console foundry:load-fixtures all`` will load both stories ``CategoryStory`` and ``PostStory``.

.. tip::

    It is possible to call a story inside another story, by using `OtherStory::load();`. Because the stories are only
    loaded once, it will work regardless of the order of the stories.

Using in your Tests
-------------------

Traditionally, data fixtures are defined in one or more files outside of your tests. When writing tests using these
fixtures, your fixtures are a sort of a *black box*. There is no clear connection between the fixtures and what you
are testing.

Foundry allows each individual test to fully follow the `AAA <https://www.thephilocoder.com/unit-testing-aaa-pattern/>`_
("Arrange", "Act", "Assert") testing pattern. You create your fixtures using "factories" at the beginning of each test.
You only create fixtures that are applicable for the test. Additionally, these fixtures are created with only the
attributes required for the test - attributes that are not applicable are filled with random data.

Let's look at an example:

::

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
        static::ensureKernelShutdown(); // Note kernel must be shutdown if you use factories before create client
        $client = static::createClient();
        $client->request('GET', '/posts/post-a'); // Note the slug from the arrange step
        $client->submitForm('Add', [
            'comment[name]' => 'John',
            'comment[body]' => 'My comment',
        ]);

        // 3. "Assert"
        self::assertResponseRedirects('/posts/post-a');

        CommentFactory::assert()->exists([ // Doctrine repository assertions
            'name' => 'John',
            'body' => 'My comment',
        ]);

        CommentFactory::assert()->count(2, ['post' => $post]); // assert given $post has 2 comments
    }

.. _enable-foundry-in-your-testcase:

Enable Foundry in your TestCase
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Add the ``Factories`` trait for tests using factories:

::

    use App\Factory\PostFactory;
    use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
    use Zenstruck\Foundry\Test\Factories;

    class MyTest extends WebTestCase
    {
        use Factories;

        public function test_1(): void
        {
            $post = PostFactory::createOne();

            // ...
        }
    }

Database Reset
~~~~~~~~~~~~~~

This library requires that your database be reset before each test. The packaged ``ResetDatabase`` trait handles
this for you.

::

    use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
    use Zenstruck\Foundry\Test\Factories;
    use Zenstruck\Foundry\Test\ResetDatabase;

    class MyTest extends WebTestCase
    {
        use ResetDatabase, Factories;

        // ...
    }

Before the first test using the ``ResetDatabase`` trait, it drops (if exists) and creates the test database.
Then, by default, before each test, it resets the schema using ``doctrine:schema:drop``/``doctrine:schema:create``.

.. tip::

    Create a base TestCase for tests using factories to avoid adding the traits to every TestCase.

.. tip::

    If your tests :ref:`are not persisting <without-persisting>` the objects they create, the ``ResetDatabase``
    trait is not required.

By default, ``ResetDatabase`` resets the default configured connection's database and default configured object manager's
schema. To customize the connection's and object manager's to be reset (or reset multiple connections/managers), use the
bundle's configuration:

.. configuration-block::

    .. code-block:: yaml

        # config/packages/zenstruck_foundry.yaml
        when@dev: # see Bundle Configuration section about sharing this in the test environment
            zenstruck_foundry:
                orm:
                    reset:
                        connections:
                            - orm_connection_1
                            - orm_connection_2
                        entity_managers:
                            - orm_object_manager_1
                            - orm_object_manager_2
                        mode: schema # default value, enables resetting the schema with doctrine:schema commands
                mongo:
                    reset:
                        document_managers:
                            - odm_object_manager_1
                            - odm_object_manager_2

Resetting using migrations
..........................

Alternatively, you can have it run your migrations instead by modifying the ``orm.reset.mode`` option in configuration file.
When using this *mode*, before each test, the database is dropped/created and your migrations run (via
``doctrine:migrations:migrate``). This mode can really make your test suite slow (especially if you have a lot of
migrations). It is highly recommended to use `DamaDoctrineTestBundle`_ to improve the
speed. When this bundle is enabled, the database is dropped/created and migrated only once for the suite.

Additionally, it is possible to provide `configuration files <https://www.doctrine-project.org/projects/doctrine-migrations/en/current/reference/configuration.html#migrations-configuration>`_
to be used by the migrations. The configuration files can be in any format supported by Doctrine Migrations (php, xml,
json, yml). Then the command ``doctrine:migrations:migrate`` will run as many times as the number of configuration
files.

.. configuration-block::

    .. code-block:: yaml

        # config/packages/zenstruck_foundry.yaml
        when@dev: # see Bundle Configuration section about sharing this in the test environment
            zenstruck_foundry:
                orm:
                    reset:
                        mode: migrate # enables resetting with migrations

                        # optional: allows you to pass additional configuration to the doctrine:migrations:migrate command
                        migrations:
                            configurations:
                                - '%kernel.root_dir%/migrations/configuration.php'
                                - 'migrations/configuration.yaml'

Extending reset mechanism
.........................

The reset mechanism can be extended thanks to decoration:

::

    use Symfony\Component\DependencyInjection\Attribute\AsDecorator;
    use Symfony\Component\DependencyInjection\Attribute\When;
    use Symfony\Component\HttpKernel\KernelInterface;
    use Zenstruck\Foundry\ORM\ResetDatabase\OrmResetter;

    // The decorator should be declared in test environment only.
    #[When('test')]
    // You can also decorate `MongoResetter::class`.
    #[AsDecorator(OrmResetter::class)]
    final readonly class DecorateDatabaseResetter implements OrmResetter
    {
        public function __construct(
            private OrmResetter $decorated
        ) {}

        public function resetBeforeFirstTest(KernelInterface $kernel): void
        {
            // do something once per test suite (for instance: install a PostgreSQL extension)

            $this->decorated->resetBeforeFirstTest($kernel);
        }

        public function resetBeforeEachTest(KernelInterface $kernel): void
        {
            // do something once per test case (for instance: restart PostgreSQL sequences)

            $this->decorated->resetBeforeEachTest($kernel);
        }
    }

If using a standard Symfony Flex app, this will be autowired/autoconfigured. If not, register the service

.. _auto-refresh:

Auto-Refresh
~~~~~~~~~~~~

.. warning::

    Auto-refresh mechanism leverages `PHP 8.4 lazy objects <https://www.php.net/manual/en/language.oop5.lazy-objects.php>`_,
    so this feature is only available when using PHP 8.4 or later.

.. info::

    For PHP versions older than PHP 8.4, auto-refresh is made using :ref:`Proxy mechanism <object-proxy>`.

Foundry provides a mechanism to automatically refresh inside a functional test the objects created by factories:

::

    use App\Factory\PostFactory;
    use Zenstruck\Foundry\Test\Factories;
    use Zenstruck\Foundry\Test\ResetDatabase;

    class MyTest extends WebTestCase
    {
        use Factories, ResetDatabase;

        public function test_with_autorefresh(): void
        {
            $post = PostFactory::createOne(['title' => 'My Title']);

            $client = self::createClient();
            $client->request('GET', "/update-post/{$post->id}", ['title' => 'New Title']);
            self::assertResponseIsSuccessful();

            // no need to manually refresh the post from the database, it has been automatically refreshed
            $this->assertSame('New Title', $post->getTitle());
        }
    }

This will work for HTTP calls simulated by the client, as well as testing commands or message handlers.

You can enable auto-refreshing in the config:

.. configuration-block::

    .. code-block:: yaml

        # config/packages/zenstruck_foundry.yaml
        when@dev: # see Bundle Configuration section about sharing this in the test environment
            zenstruck_foundry:
                enable_auto_refresh_with_lazy_objects: true

.. _object-proxy:

Object Proxy
~~~~~~~~~~~~

.. warning::

    Object proxy are deprecated since Foundry 2.7. Please use `auto-refresh`_ mechanism instead (PHP 8.4 only).
    See `the upgrade guide to v2.7 <https://github.com/zenstruck/foundry/blob/2.x/UPGRADE-2.7.md>`_

Objects created by a factory extending ``PersistentProxyObjectFactory`` are wrapped in a special *Proxy* object.
These objects allow your doctrine entities to have `Active Record <https://en.wikipedia.org/wiki/Active_record_pattern>`_ *like* behavior:

::

    use App\Factory\PostFactory;

    $post = PostFactory::createOne(['title' => 'My Title']); // instance of Zenstruck\Foundry\Proxy

    // get the wrapped object
    $realPost = $post->_real(); // instance of Post

    // call any Post method
    $post->getTitle(); // "My Title"

    // set property and save to the database
    $post->setTitle('New Title');
    $post->_save();

    // refresh from the database
    $post->_refresh();

    // delete from the database
    $post->_delete();

    $post->_repository(); // repository proxy wrapping PostRepository (see Repository Proxy section below)

Force Setting
.............

Object proxies have helper methods to access non-public properties of the object they wrap:

::

    // set private/protected properties
    $post->_set('createdAt', new \DateTime());

    // get private/protected properties
    $post->_get('createdAt');

Auto-Refresh
............

Object proxies have the option to enable *auto refreshing* that removes the need to call ``->_refresh()`` before calling
methods on the underlying object. When auto-refresh is enabled, most calls to proxy objects first refresh the wrapped
object from the database. This is mainly useful with "integration" test which interacts with your database and Symfony's
kernel.

::

    use App\Factory\PostFactory;

    $post = PostFactory::new(['title' => 'Original Title'])
        ->create()
        ->_enableAutoRefresh()
    ;

    // ... logic that changes the $post title to "New Title" (like your functional test)

    $post->getTitle(); // "New Title" (equivalent to $post->_refresh()->getTitle())

Without auto-refreshing enabled, the above call to ``$post->getTitle()`` would return "Original Title".

.. note::

    A situation you need to be aware of when using auto-refresh is that all methods refresh the object first. If
    changing the object's state via multiple methods (or multiple force-sets), an "unsaved changes" exception will be
    thrown:

::

        use App\Factory\PostFactory;

        $post = PostFactory::new(['title' => 'Original Title', 'body' => 'Original Body'])
            ->create()
            ->_enableAutoRefresh()
        ;

        $post->setTitle('New Title');
        $post->setBody('New Body'); // exception thrown because of "unsaved changes" to $post from above

To overcome this, you need to first disable auto-refreshing, then re-enable after making/saving the changes:

::

        use App\Entity\Post;
        use App\Factory\PostFactory;

        $post = PostFactory::new(['title' => 'Original Title', 'body' => 'Original Body'])
            ->create()
            ->_enableAutoRefresh()
        ;

        $post->_disableAutoRefresh();
        $post->setTitle('New Title'); // or using ->_set('title', 'New Title')
        $post->setBody('New Body'); // or using ->_set('body', 'New Body')
        $post->_enableAutoRefresh();
        $post->_save();

        $post->getBody(); // "New Body"
        $post->getTitle(); // "New Title"

        // alternatively, use the ->_withoutAutoRefresh() helper which first disables auto-refreshing, then re-enables after
        // executing the callback.
        $post->_withoutAutoRefresh(function (Post $post) { // can pass either Post or Proxy to the callback
            $post->setTitle('New Title');
            $post->setBody('New Body');
        });
        $post->_save();

Proxy objects pitfalls
......................

Proxified objects may have some pitfalls when dealing with Doctrine's entity manager. You may encounter this error:

.. code-block:: text

    > Doctrine\ORM\ORMInvalidArgumentException: A new entity was found through the relationship
    'App\Entity\Post#category' that was not configured to cascade persist operations for entity: AppEntityCategoryProxy@3082.
    To solve this issue: Either explicitly call EntityManager#persist() on this unknown entity or configure cascade persist
    this association in the mapping for example @ManyToOne(..,cascade={"persist"}). If you cannot find out which entity
    causes the problem implement 'App\Entity\Category#__toString()' to get a clue.

The problem will occur if a proxy has been passed to ``EntityManager::persist()``. To fix this, you should pass the "real"
object, by calling ``$proxyfiedObject->_real()``.

Factory without proxy
.....................

It is possible to create factories which do not create "proxified" objects. Instead of making your factory inherit from
``PersistentProxyObjectFactory``, you can inherit from ``PersistentObjectFactory``. Your factory will then directly return
the "real" object, which won't be wrapped by ``Proxy`` class.

.. warning::

    Be aware that your object won't refresh automatically if they are not wrapped with a proxy.

Repository Decorator
~~~~~~~~~~~~~~~~~~~~

This library provides a *Repository Decorator* that wraps your object repositories to provide useful assertions and methods:

::

    use App\Entity\Post;
    use App\Factory\PostFactory;
    use function Zenstruck\Foundry\Persistence\repository;

    // instance of RepositoryProxy that wraps PostRepository
    $repository = PostFactory::repository();

    // alternative to above for getting a repository for which you haven't created factories for
    $repository = repository(Post::class);

    // helpful methods - all returned object(s) are proxied
    $repository->inner(); // the real "wrapped" repository
    $repository->count(); // number of rows in the database table
    count($repository); // equivalent to above (RepositoryDecorator implements \Countable)
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
    $repository->find(1); // Post|null
    $repository->find(['title' => 'My Title']); // Post|null
    $repository->findOneBy(['title' => 'My Title']); // Post|null
    $repository->findAll(); // Post[]
    iterator_to_array($repository); // equivalent to above (RepositoryDecorator implements \IteratorAggregate)
    $repository->findBy(['title' => 'My Title']); // Post[]

    // can call methods on the underlying repository (RepositoryDecorator is a "@mixin" of its wrapped repository)
    $repository->findOneByTitle('My Title'); // Post|null

Assertions
~~~~~~~~~~

Foundry provides helpful PHPUnit assertions:

::

    use App\Factory\PostFactory;
    use function Zenstruck\Foundry\Persistence\assert_not_persisted;
    use function Zenstruck\Foundry\Persistence\assert_persisted;

    $post = PostFactory::createOne();
    assert_persisted($post);
    assert_not_persisted($post);

    PostFactory::assert()->empty();
    PostFactory::assert()->count(3);
    PostFactory::assert()->countGreaterThan(3);
    PostFactory::assert()->countGreaterThanOrEqual(3);
    PostFactory::assert()->countLessThan(3);
    PostFactory::assert()->countLessThanOrEqual(3);
    PostFactory::assert()->exists(['title' => 'My Title']);
    PostFactory::assert()->notExists(['title' => 'My Title']);

.. _global-state:

Global State
~~~~~~~~~~~~

If you have an initial database state you want for all tests, you can set this in the config of the bundle. Accepted
values are: stories as service, "global" stories and invokable services. Global state is loaded before each test using
the ``ResetDatabase`` trait. If you are using `DamaDoctrineTestBundle`_, it is only loaded once for the entire
test suite.

.. configuration-block::

    .. code-block:: yaml

        # config/packages/zenstruck_foundry.yaml
        when@test: # see Bundle Configuration section about sharing this in the test environment
            zenstruck_foundry:
                global_state:
                    - App\Story\StoryThatIsAService
                    - App\Story\GlobalStory
                    - invokable.service # just a service with ::invoke()
                    - ...

.. note::

    You can still access `Story State`_ for *Global State Stories* in your tests and they are still
    only loaded once.

.. note::

    The :ref:`ResetDatabase <enable-foundry-in-your-testcase>` trait is required when using global state.

.. warning::

    Be aware that a complex global state could slow down your test suite.

PHPUnit Data Providers
~~~~~~~~~~~~~~~~~~~~~~

It is possible to use factories in
`PHPUnit data providers <https://docs.phpunit.de/en/11.5/writing-tests-for-phpunit.html#data-providers>`_.
Their usage depends on whether you're using Foundry's `PHPUnit Extension`_ or not.:

With PHPUnit Extension
......................

.. versionadded::  2.2

    The ability to call ``Factory::create()`` in data providers was introduced in Foundry 2.2.

.. warning::

    You will need at least PHPUnit 11.4 to call ``Factory::create()`` in your data providers.

Thanks to Foundry's `PHPUnit Extension`_, you'll be able to use your factories in your data providers the same way
you're using them in tests. Thanks to it, you can:

* Call ``->create()`` or ``::createOne()`` or any other method which creates objects in unit tests
  (using ``PHPUnit\Framework\TestCase``) and functional tests (``Symfony\Bundle\FrameworkBundle\Test\KernelTestCase``);
* Use `Factories as Services`_ in functional tests;
* Use ``faker()`` normally, without wrapping its call in a callable.

::

    use App\Factory\PostFactory;
    use PHPUnit\Framework\Attributes\DataProvider;

    #[DataProvider('createMultipleObjectsInDataProvider')]
    public function test_post_via_data_provider(Post $post): void
    {
        // at this point, `$post` exists, and is already stored in database
    }

    public static function postDataProvider(): iterable
    {
        yield [PostFactory::createOne()];
        yield [PostWithServiceFactory::createOne()];
        yield [PostFactory::createOne(['body' => faker()->sentence()];
    }

.. warning::

    With PHP < 8.4, Foundry is relying on its :ref:`Proxy mechanism <object-proxy>`, when using persistence,
    your factories must extend ``Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory`` to work in your data providers
    (with PHP >= 8.4, it will work out of the box).

.. warning::

    When in a data provider, calling ``->create()`` or ``::createOne()`` will return a `PHP 8.4 lazy objects <https://www.php.net/manual/en/language.oop5.lazy-objects.php>`_.
    You should not try to access any of its properties or methods inside the data provider, otherwise it will trigger
    the persist mechanism, which is something you don't want to do in a data provider (it will fail, anyway).
    For the same reason, on PHP versions above PHP 8.4, you should not call methods from ``Proxy`` class in your
    data providers, not even ``->_real()``.


Without PHPUnit Extension
.........................

Data providers are computed early in the phpunit process before Foundry is booted.
Be sure your data provider returns only instances of ``Factory`` and you do not try to call ``->create()`` on them:

::

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

.. note::

    For the same reason as above, it is not possible to use `Factories as Services`_ with required
    constructor arguments (the container is not yet available).

.. note::

    Still for the same reason, if `Faker`_ is needed along with ``->with()`` within a data provider, you'll need
    to pass attributes as a *callable*.

    Given the data provider of the previous example, here is ``PostFactory::published()``

::

        public function published(): self
        {
            // This won't work in a data provider!
            // return $this->with(['published_at' => self::faker()->dateTime()]);

            // use this instead:
            return $this->with(
                static fn() => [
                    'published_at' => self::faker()->dateTime()
                ]
            );
        }

.. tip::

    ``ObjectFactory::new()->many()`` and ``ObjectFactory::new()->sequence()`` return a special ``FactoryCollection`` object
    which can be used to generate data providers:

::

        use App\Factory\PostFactory;

        /**
         * @dataProvider postDataProvider
         */
        public function test_post_via_data_provider(PostFactory $factory): void
        {
            $factory->create();

            // ...
        }

        public static function postDataProvider(): iterable
        {
            yield from PostFactory::new()->sequence(
                [
                    ['title' => 'foo'],
                    ['title' => 'bar'],
                ]
            )->asDataProvider();
        }

The ``FactoryCollection`` could also be passed directly to the test case in order to have several objects available in the same test:

::

        use App\Factory\PostFactory;

        /**
         * @dataProvider postDataProvider
         */
        public function test_post_via_data_provider(FactoryCollection $factoryCollection): void
        {
            $factoryCollection->create();

            // ...
        }

        public static function postDataProvider(): iterable
        {
            // 3 posts will be created for the first test case
            yield PostFactory::new()->sequence(
                [
                    ['title' => 'foo 1'],
                    ['title' => 'bar 1'],
                    ['title' => 'baz 1'],
                ]
            );

            // 2 posts will be created for the second test case
            yield PostFactory::new()->sequence(
                [
                    ['title' => 'foo 2'],
                    ['title' => 'bar 2'],
                ]
            );
        }


Performance
~~~~~~~~~~~

The following are possible options to improve the speed of your test suite.

DAMADoctrineTestBundle
......................

This library integrates seamlessly with `DAMADoctrineTestBundle <https://github.com/dmaicher/doctrine-test-bundle>`_ to
wrap each test in a transaction which dramatically reduces test time. This library's test suite runs 5x faster with
this bundle enabled.

Follow its documentation to install. Foundry's ``ResetDatabase`` trait detects when using the bundle and adjusts
accordingly. Your database is still reset before running your test suite but the schema isn't reset before each test
(just the first).

.. note::

    If using `Global State`_, it is persisted to the database (not in a transaction) before your
    test suite is run. This could further improve test speed if you have a complex global state.

.. caution::

    Using `Global State`_ that creates both ORM and ODM factories when using DAMADoctrineTestBundle
    is not supported.

paratestphp/paratest
....................

You can use `paratestphp/paratest <https://github.com/paratestphp/paratest>`_ to run your tests in parallel.
This can dramatically improve test speed. The following considerations need to be taken into account:

1. Your doctrine package configuration needs to have paratest's ``TEST_TOKEN`` environment variable in
   the database name. This is so each parallel process has its own database. For example:

   .. code-block:: yaml

       # config/packages/doctrine.yaml
       when@test:
           doctrine:
               dbal:
                   dbname_suffix: '_test%env(default::TEST_TOKEN)%'

2. If using `DAMADoctrineTestBundle`_ and ``paratestphp/paratest`` < 7.0, you need to set the ``--runner`` option to
   ``WrapperRunner``. This is so the database is reset once per process (without this option, it is reset once per
   test class).

   .. code-block:: terminal

       vendor/bin/paratest --runner WrapperRunner

3. If running with debug mode disabled, you need to adjust the `Disable Debug Mode`_ code to the following:

   ::

       // tests/bootstrap.php
       // ...
       if (false === (bool) $_SERVER['APP_DEBUG'] && null === ($_SERVER['TEST_TOKEN'] ?? null)) {
           /*
            * Ensure a fresh cache when debug mode is disabled. When using paratest, this
            * file is required once at the very beginning, and once per process. Checking that
            * TEST_TOKEN is not set ensures this is only run once at the beginning.
            */
           (new Filesystem())->remove(__DIR__.'/../var/cache/test');
       }

Disable Debug Mode
..................

In your ``.env.test`` file, you can set ``APP_DEBUG=0`` to have your tests run without debug mode. This can speed up
your tests considerably. You will need to ensure you cache is cleared before running the test suite. The best place to
do this is in your ``tests/bootstrap.php``:

::

    // tests/bootstrap.php
    // ...
    if (false === (bool) $_SERVER['APP_DEBUG']) {
        // ensure fresh cache
        (new Symfony\Component\Filesystem\Filesystem())->remove(__DIR__.'/../var/cache/test');
    }

Reduce Password Encoder *Work Factor*
.....................................

If you have a lot of tests that work with encoded passwords, this will cause these tests to be unnecessarily slow.
You can improve the speed by reducing the *work factor* of your encoder:

.. code-block:: yaml

    # config/packages/test/security.yaml
    encoders:
        # use your user class name here
        App\Entity\User:
            # This should be the same value as in config/packages/security.yaml
            algorithm: auto
            cost: 4 # Lowest possible value for bcrypt
            time_cost: 3 # Lowest possible value for argon
            memory_cost: 10 # Lowest possible value for argon

Pre-Encode Passwords
....................

Pre-encode user passwords with a known value via ``bin/console security:hash-password`` and set this in
``defaults()``. Add the known value as a ``const`` on your factory:

::

    class UserFactory extends PersistentObjectFactory
    {
        public const DEFAULT_PASSWORD = '1234'; // the password used to create the pre-encoded version below

        protected function defaults(): array
        {
            return [
                // ...
                'password' => '$argon2id$v=19$m=65536,t=4,p=1$pLFF3D2gnvDmxMuuqH4BrA$3vKfv0cw+6EaNspq9btVAYc+jCOqrmWRstInB2fRPeQ',
            ];
        }
    }

Now, in your tests, when you need access to the unencoded password for a user created with ``UserFactory``, use
``UserFactory::DEFAULT_PASSWORD``.

Non-Kernel Tests
~~~~~~~~~~~~~~~~

Foundry can be used in standard PHPUnit unit tests (TestCase's that just extend ``PHPUnit\Framework\TestCase`` and not
``Symfony\Bundle\FrameworkBundle\Test\KernelTestCase``). These tests still require using the ``Factories`` trait to boot
Foundry but will not have doctrine available. Factories created in these tests will not be persisted (calling
``->withoutPersisting()`` is not necessary). Because the bundle is not available in these tests,
any bundle configuration you have will not be picked up.

::

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

You will need to configure manually Foundry. Unfortunately, this may mean duplicating your bundle configuration here.

::

    // tests/bootstrap.php
    // ...

    Zenstruck\Foundry\Test\UnitTestConfig::configure(
        instantiator: Zenstruck\Foundry\Object\Instantiator::withoutConstructor()
            ->allowExtra()
            ->alwaysForce(),
        faker: Faker\Factory::create('fr_FR')
    );

.. note::

    `Factories as Services`_ and `Stories as Services`_ with required
    constructor arguments are not usable in non-Kernel tests. The container is not available to resolve their dependencies.
    The easiest work-around is to make the test an instance of ``Symfony\Bundle\FrameworkBundle\Test\KernelTestCase`` so the
    container is available.

.. _stories:

In-memory Behavior
~~~~~~~~~~~~~~~~~~

Foundry allows to use "in-memory" repositories in your factories. This is mainly useful for `DDD <https://en.wikipedia.org/wiki/Domain-driven_design>`_
applications or with `hexagonal architecture <https://en.wikipedia.org/wiki/Hexagonal_architecture_(software)>_`, where
repositories in the domain are usually interfaces for which main implementations are Doctrine ones. You can tell Foundry to
use the "in-memory" version of these repositories.

.. versionadded:: 2.5

    The "in-memory" behavior was added in Foundry 2.5 and is experimental. Experimental features are not
    covered by the backward compatibility promise.

.. warning::

    The `PHPUnit Extension`_ for Foundry is needed to use "in-memory" behavior (along with PHPUnit ^11.4).

First, you need to create an "in-memory" version of your repository. This repository must implement the
``Zenstruck\Foundry\InMemory\InMemoryRepository`` interface. You can use the trait
``Zenstruck\Foundry\InMemory\InMemoryRepositoryTrait`` to help you with this:

::

    use App\Domain\Address\DomainAddressRepositoryInterface;
    use Zenstruck\Foundry\InMemory\InMemoryRepository;
    use Zenstruck\Foundry\InMemory\InMemoryRepositoryTrait;

    /**
     * @implements InMemoryRepository<Address>
     */
    final class InMemoryAddressRepository implements InMemoryRepository, DomainAddressRepositoryInterface
    {
        /** @use InMemoryRepositoryTrait<Address> */
        use InMemoryRepositoryTrait;

        // The class returned by this method is the class managed by this repository
        public static function _class(): string
        {
            return Address::class;
        }

        // + all methods implementing "DomainAddressRepository"
    }

Then, the "in-memory" repository should be used in Symfony's container, as the main repository implementation. For this
purpose, you can either use a new environment ``test-in-memory``, or use a ``InMemoryKernel``.

In your tests, use the ``#[AsInMemoryTest]`` attribute, which will disable persistence of the factories, and register an
"after instantiate" hook, which will store the objects in their respective "in memory" repositories:

::

    use Zenstruck\Foundry\InMemory\AsInMemoryTest;

    #[AsInMemoryTest]
    final class SomeTest extends KernelTestCase
    {
        private InMemoryAddressRepository $addressRepository;

        protected function setUp(): void
        {
            $this->addressRepository = self::getContainer()->get(InMemoryAddressRepository::class);
        }

        #[Test]
        public function object_should_be_accessible_from_in_memory_repository(): void
        {
            $address = AddressFactory::createOne();

            self::assertSame([$address], $this->addressRepository->_all());

            // The following assertion is also true, `YourFactory::repository()` returns a special "in-memory" repository
            // no request to the database will be made.
            self::assertSame(1, AddressFactory::repository()->count(1));

            // You can even use `YourFactory::repository()->assert()`
            AddressFactory::repository()->assert()->count(1);
        }

        protected static function getKernelClass(): string
        {
            // This is one of the ways to use the "in-memory" repositories in a "kernel test":
            // the "InMemoryKernel" would use "in-memory" repositories instead of the main ones.
            return InMemoryKernel::class;
        }
    }

A ``GenericInMemoryRepository`` class is also provided for convenience, when the "in-memory" repository is missing for a
specific class. This way, you're not forced to create a "in-memory" version for all your repositories, but only for the
ones used in the current test.

Static Analysis
---------------

Psalm
~~~~~

A Psalm extension is shipped with the library.
Please, enable it with:

.. code-block:: terminal

    $ vendor/bin/psalm-plugin enable zenstruck/foundry

PHPUnit Extension
-----------------

Foundry is shipped with an extension for PHPUnit. You can install it by modifying the file ``phpunit.xml.dist``:

.. configuration-block::

    .. code-block:: xml

        <phpunit>
            <extensions>
                <bootstrap class="Zenstruck\Foundry\PHPUnit\FoundryExtension"/>
            </extensions>
        </phpunit>

This extension provides the following features:

* support for the `#[WithStory] Attribute`_
* ability to use ``Factory::create()`` in `PHPUnit Data Providers`_ (along with PHPUnit ^11.4)

.. versionadded:: 2.2

    The PHPUnit extension was introduced in Foundry 2.2.

.. warning::

    The PHPUnit extension is only compatible with PHPUnit 10+.

Bundle Configuration
--------------------

Since the bundle is intended to be used in your *dev* and *test* environments, you'll want the configuration
for each environment to match. The easiest way to do this is to use *YAML anchors* with ``when@dev``/``when@test``.
This way, there is just one place to set your config.

.. configuration-block::

    .. code-block:: yaml

        # config/packages/zenstruck_foundry.yaml
        when@dev: &dev
            zenstruck_foundry:
                # ... put all your config here

        when@test: *dev # "copies" the config from above

Full Default Bundle Configuration
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. code-block:: yaml

    zenstruck_foundry:
        # Enable auto-refresh with lazy objects (PHP >= 8.4 only).
        enable_auto_refresh_with_lazy_objects: false

        # Configure faker to be used by your factories.
        faker:

            # Change the default faker locale.
            locale:               null # Example: fr_FR

            # Customize the faker service.
            service:              null # Example: my_faker

        # Configure the default instantiator used by your factories.
        instantiator:

            # Use the constructor to instantiate objects.
            use_constructor:      ~

            # Whether or not to allow extra attributes.
            allow_extra_attributes: false

            # Whether or not to skip setters and force set object properties (public/private/protected) directly.
            always_force_properties: false

            # Customize the instantiator service.
            service:              null # Example: my_instantiator
        orm:
            reset:

                # DBAL connections to reset with ResetDatabase trait
                connections:

                    # Default:
                    - default

                # Entity Managers to reset with ResetDatabase trait
                entity_managers:

                    # Default:
                    - default

                # Reset mode to use with ResetDatabase trait
                mode:                 schema # One of "schema"; "migrate"
                migrations:

                    # Migration configurations
                    configurations:       []

        mongo:
            reset:

                # Document Managers to reset with ResetDatabase trait
                document_managers:

                    # Default:
                    - default

        # Array of stories that should be used as global state.
        global_state:         []

        make_factory:

            # Default namespace where factories will be created by maker.
            default_namespace:    Factory

            # Add "beginner" hints in the created factory.
            add_hints:    true
        make_story:

            # Default namespace where stories will be created by maker.
            default_namespace:    Story
