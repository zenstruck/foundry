Foundry
=======

Foundry makes creating fixtures data fun again, via an expressive, auto-completable, on-demand fixtures system with
Symfony and Doctrine:

The factories can be used inside `DoctrineFixturesBundle <https://symfony.com/bundles/DoctrineFixturesBundle/current/index.html>`_
to load fixtures or inside your tests, `where it has even more features <https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#using-in-your-tests>`_.

Foundry supports ``doctrine/orm`` (with `doctrine/doctrine-bundle <https://github.com/doctrine/doctrinebundle>`_),
``doctrine/mongodb-odm`` (with `doctrine/mongodb-odm-bundle <https://github.com/doctrine/DoctrineMongoDBBundle>`_)
or a combination of these.

Want to watch a screencast 🎥 about it? Check out https://symfonycasts.com/foundry

Installation
------------

.. code-block:: terminal

    $ composer require zenstruck/foundry --dev

To use the ``make:*`` commands from this bundle, ensure
`Symfony MakerBundle <https://symfony.com/bundles/SymfonyMakerBundle/current/index.html>`_ is installed.

*If not using Symfony Flex, be sure to enable the bundle in your **test**/**dev** environments.*

Same Entities used in these Docs
--------------------------------

For the remainder of the documentation, the following sample entities will be used:

.. code-block:: php

    namespace App\Entity;

    use App\Repository\CategoryRepository;
    use Doctrine\ORM\Mapping as ORM;

    #[ORM\Entity(repositoryClass: CategoryRepository::class)]
    class Category
    {
        #[ORM\Id]
        #[ORM\GeneratedValue]
        #[ORM\Column(type: 'string')]
        private $id;

        #[ORM\Column(type: 'string', length: 255)]
        private $name;

        public function __construct(string $name)
        {
            $this->name = $name;
        }

        // ... getters/setters
    }

.. code-block:: php

    namespace App\Entity;

    use App\Repository\PostRepository;
    use Doctrine\ORM\Mapping as ORM;

    #[ORM\Entity(repositoryClass: PostRepository::class)]
    class Post
    {
        #[ORM\Id]
        #[ORM\GeneratedValue]
        #[ORM\Column(type: 'string')]
        private $id;

        #[ORM\Column(type: 'string', length: 255)]
        private $title;

        #[ORM\Column(type: 'text', nullable: true)]
        private $body;

        #[ORM\Column(type: 'datetime')]
        private $createdAt;

        #[ORM\Column(type: 'datetime', nullable: true)]
        private $publishedAt;

        #[ORM\ManyToOne(targetEntity: Category::class)]
        #[ORM\JoinColumn]
        private $category;

        public function __construct(string $title)
        {
            $this->title = $title;
            $this->createdAt = new \DateTime('now');
        }

        // ... getters/setters
    }

Model Factories
---------------

The nicest way to use Foundry is to generate one *factory* class per entity. You can skip this
and use `Anonymous Factories`_, but *model factories* give you IDE auto-completion
and access to other useful features.

Generate
~~~~~~~~

Create a model factory for one of your entities with the maker command:

.. code-block:: terminal

    $ bin/console make:factory

    > Entity class to create a factory for:
    > Post

    created: src/Factory/PostFactory.php

    Next: Open your new factory and set default values/states.

This command will generate a ``PostFactory`` class that looks like this:

.. code-block:: php

    // src/Factory/PostFactory.php

    namespace App\Factory;

    use App\Entity\Post;
    use App\Repository\PostRepository;
    use Zenstruck\Foundry\RepositoryProxy;
    use Zenstruck\Foundry\ModelFactory;
    use Zenstruck\Foundry\Proxy;

    /**
     * @extends ModelFactory<Post>
     *
     * @method        Post|Proxy create(array|callable $attributes = [])
     * @method static Post|Proxy createOne(array $attributes = [])
     * @method static Post|Proxy find(object|array|mixed $criteria)
     * @method static Post|Proxy findOrCreate(array $attributes)
     * @method static Post|Proxy first(string $sortedField = 'id')
     * @method static Post|Proxy last(string $sortedField = 'id')
     * @method static Post|Proxy random(array $attributes = [])
     * @method static Post|Proxy randomOrCreate(array $attributes = []))
     * @method static PostRepository|RepositoryProxy repository()
     * @method static Post[]|Proxy[] all()
     * @method static Post[]|Proxy[] createMany(int $number, array|callable $attributes = [])
     * @method static Post[]&Proxy[] createSequence(iterable|callable $sequence)
     * @method static Post[]|Proxy[] findBy(array $attributes)
     * @method static Post[]|Proxy[] randomRange(int $min, int $max, array $attributes = []))
     * @method static Post[]|Proxy[] randomSet(int $number, array $attributes = []))
     */
    final class PostFactory extends ModelFactory
    {
        /**
         * @see https://github.com/zenstruck/foundry#factories-as-services
         *
         * @todo inject services if required
         */
        public function __construct()
        {
            parent::__construct();
        }

        /**
         * @see https://github.com/zenstruck/foundry#model-factories
         *
         * @todo add your default values here
         */
        protected function getDefaults(): array
        {
            return [];
        }

        /**
         * @see https://github.com/zenstruck/foundry#initialization
         */
        protected function initialize(): self
        {
            return $this
                // ->afterInstantiate(function(Post $post) {})
            ;
        }

        protected static function getClass(): string
        {
            return Post::class;
        }
    }

.. tip::

    Using ``make:factory --test`` will generate the factory in ``tests/Factory``.

.. tip::

    You can globally configure which namespace the factories will be generated in:

    .. configuration-block::

        .. code-block:: yaml

            # config/packages/zenstruck_foundry.yaml
            when@dev: # see Bundle Configuration section about sharing this in the test environment
                zenstruck_foundry:
                    make_factory:
                        default_namespace: 'App\\MyFactories'

    You can override this configuration by using the ``--namespace`` option.


.. note::

    The generated ``@method`` docblocks above enable autocompletion with PhpStorm but
    cause errors with PHPStan and Psalm. To support PHPStan or Psalm for your factory's, you need to *also*
    add the following dockblocks (replace ``phpstan-`` prefix by ``psalm-`` accordingly to your static analysis tool):

    .. code-block:: php

        /**
         * ...
         *
         * @phpstan-method        Proxy<Post> create(array|callable $attributes = [])
         * @phpstan-method static Proxy<Post> createOne(array $attributes = [])
         * @phpstan-method static Proxy<Post> find(object|array|mixed $criteria)
         * @phpstan-method static Proxy<Post> findOrCreate(array $attributes)
         * @phpstan-method static Proxy<Post> first(string $sortedField = 'id')
         * @phpstan-method static Proxy<Post> last(string $sortedField = 'id')
         * @phpstan-method static Proxy<Post> random(array $attributes = [])
         * @phpstan-method static Proxy<Post> randomOrCreate(array $attributes = [])
         * @phpstan-method static RepositoryProxy<Post> repository()
         * @phpstan-method static list<Proxy<Post>> all()
         * @phpstan-method static list<Proxy<Post>> createMany(int $number, array|callable $attributes = [])
         * @phpstan-method static list<Proxy<Post>> createSequence(iterable|callable $sequence)
         * @phpstan-method static list<Proxy<Post>> findBy(array $attributes)
         * @phpstan-method static list<Proxy<Post>> randomRange(int $min, int $max, array $attributes = [])
         * @phpstan-method static list<Proxy<Post>> randomSet(int $number, array $attributes = [])
         */
        final class PostFactory extends ModelFactory
        {
            // ...
        }

In the ``getDefaults()``, you can return an array of all default values that any new object
should have. `Faker`_ is available to easily get random data:

.. code-block:: php

    protected function getDefaults(): array
    {
        return [
            // Symfony's property-access component is used to populate the properties
            // this means that setTitle() will be called or you can have a $title constructor argument
            'title' => self::faker()->unique()->sentence(),
            'body' => self::faker()->sentence(),
        ];
    }

.. tip::

    It is best to have ``getDefaults()`` return the attributes to persist a valid object
    (all non-nullable fields).

.. tip::

    Using ``make:factory --all-fields`` will generate default values for all fields of the entity,
    not only non-nullable fields.

.. note::

    ``getDefaults()`` is called everytime a factory is instantiated (even if you don't end up
    creating it). `Lazy values`_ allows you to ensure the value is only calculated when/if it's needed.

Using your Factory
~~~~~~~~~~~~~~~~~~

.. code-block:: php

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

    // Create 5 posts with incremental title
    PostFactory::createMany(
        5,
        static function(int $i) {
            return ['title' => "Title $i"]; // "Title 1", "Title 2", ... "Title 5"
        }
    );

    // find a persisted object for the given attributes, if not found, create with the attributes
    PostFactory::findOrCreate(['title' => 'My Title']); // returns Post|Proxy

    PostFactory::first(); // get the first object (assumes an auto-incremented "id" column)
    PostFactory::first('createdAt'); // assuming "createdAt" is a datetime column, this will return latest object
    PostFactory::last(); // get the last object (assumes an auto-incremented "id" column)
    PostFactory::last('createdAt'); // assuming "createdAt" is a datetime column, this will return oldest object

    PostFactory::truncate(); // empty the database table

    PostFactory::count(); // the number of persisted Posts
    PostFactory::count(['category' => $category); // the number of persisted Posts with the given category

    PostFactory::all(); // Post[]|Proxy[] all the persisted Posts

    PostFactory::findBy(['author' => 'kevin']); // Post[]|Proxy[] matching the filter

    $post = PostFactory::find(5); // Post|Proxy with the id of 5
    $post = PostFactory::find(['title' => 'My First Post']); // Post|Proxy matching the filter

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

Reusable Model Factory "States"
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

You can add any methods you want to your model factories (i.e. static methods that create an object in a certain way) but
you can also add *states*:

.. code-block:: php

    namespace App\Factory;

    use App\Entity\Post;
    use Zenstruck\Foundry\ModelFactory;

    final class PostFactory extends ModelFactory
    {
        // ...

        public function published(): self
        {
            // call setPublishedAt() and pass a random DateTime
            return $this->addState(['published_at' => self::faker()->dateTime()]);
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

You can use states to make your tests very explicit to improve readability:

.. code-block:: php

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

Attributes
~~~~~~~~~~

The attributes used to instantiate the object can be added several ways. Attributes can be an *array*, or a *callable*
that returns an array. Using a *callable* ensures random data as the callable is run for each object separately during
instantiation.

.. code-block:: php

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
        ->withAttributes(function() { return ['createdAt' => faker()->dateTime()]; }) // see faker section below

        // create "2" Post's
        ->many(2)->create(['title' => 'Different Title'])
    ;

    $posts[0]->getTitle(); // "Different Title"
    $posts[0]->getBody(); // "Post Body..."
    $posts[0]->getCategory(); // random Category
    $posts[0]->getPublishedAt(); // \DateTime('last week')
    $posts[0]->getCreatedAt(); // random \DateTime

    $posts[1]->getTitle(); // "Different Title"
    $posts[1]->getBody(); // "Post Body..."
    $posts[1]->getCategory(); // random Category (different than above)
    $posts[1]->getPublishedAt(); // \DateTime('last week')
    $posts[1]->getCreatedAt(); // random \DateTime (different than above)

.. note::

    Attributes passed to the ``create*`` methods are merged with any attributes set via ``getDefaults()``
    and ``withAttributes()``.


Sequences
~~~~~~~~~

Sequences help to create different objects in one call:

.. code-block:: php

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

Faker
~~~~~

This library provides a wrapper for `FakerPHP <https://fakerphp.github.io/>`_ to help with generating
random data for your factories:

.. code-block:: php

    use Zenstruck\Foundry\Factory;
    use function Zenstruck\Foundry\faker;

    Factory::faker()->name(); // random name

    // alternatively, use the helper function
    faker()->email(); // random email

.. note::

    You can customize Faker's `locale <https://fakerphp.github.io/#localization>`_ and random
    `seed <https://fakerphp.github.io/#seeding-the-generator>`_:

    .. code-block:: yaml

        # config/packages/zenstruck_foundry.yaml
        when@dev: # see Bundle Configuration section about sharing this in the test environment
            zenstruck_foundry:
                faker:
                    locale: fr_FR # set the locale
                    seed: 5678 # set the random number generator seed

.. note::

    You can register your own *Faker Provider* by tagging any service with ``foundry.faker_provider``.
    All public methods on this service will be available on Foundry's Faker instance:

    .. code-block:: php

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

Events / Hooks
~~~~~~~~~~~~~~

The following events can be added to factories. Multiple event callbacks can be added, they are run in the order
they were added.

.. code-block:: php

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
        ->afterPersist(function(Proxy $proxy, array $attributes) {
            /* @var Post $proxy */
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

You can also add hooks directly in your model factory class:

.. code-block:: php

    protected function initialize(): self
    {
        return $this
            ->afterPersist(function() {})
        ;
    }

Read `Initialization`_ to learn more about the ``initialize()`` method.

Initialization
~~~~~~~~~~~~~~

You can override your model factory's ``initialize()`` method to add default state/logic:

.. code-block:: php

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

.. note::

    Be sure to chain the states/hooks off of ``$this`` because factories are `Immutable`_.

.. _instantiation:

Instantiation
~~~~~~~~~~~~~

By default, objects are instantiated in the normal fashion, by using the object's constructor. Attributes
that match constructor arguments are used. Remaining attributes are set to the object using Symfony's
`PropertyAccess <https://symfony.com/doc/current/components/property_access.html>`_ component
(setters/public properties). Any extra attributes cause an exception to be thrown.

You can customize the instantiator in several ways:

.. code-block:: php

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
        ->instantiateWith(function(array $attributes, string $class): object {
            return new Post(); // ... your own logic
        })
    ;

You can customize the instantiator globally for all your factories (can still be overruled by factory instance
instantiators):

.. code-block:: yaml

    # config/packages/zenstruck_foundry.yaml
    when@dev: # see Bundle Configuration section about sharing this in the test environment
        zenstruck_foundry:
            instantiator:
                without_constructor: true # always instantiate objects without calling the constructor
                allow_extra_attributes: true # always ignore extra attributes
                always_force_properties: true # always "force set" properties
                # or
                service: my_instantiator # your own invokable service for complete control

Immutable
~~~~~~~~~

Factory's are immutable:

.. code-block:: php

    use App\Factory\PostFactory;

    $factory = PostFactory::new();
    $factory1 = $factory->withAttributes([]); // returns a new PostFactory object
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

.. code-block:: php

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

.. tip::

    It is recommended that the only relationship you define in ``ModelFactory::getDefaults()`` is non-null
    Many-to-One's.

.. tip::

    It is also recommended that your ``ModelFactory::getDefaults()`` return a ``Factory`` and not the created entity.
    However, you can use `Lazy values`_ if you need to create the entity in the ``getDefaults()`` method.

    .. code-block:: php

        protected function getDefaults(): array
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

.. code-block:: php

    use App\Factory\CommentFactory;
    use App\Factory\PostFactory;

    // Example 1: Create a Post with 6 Comments
    PostFactory::createOne(['comments' => CommentFactory::new()->many(6)]);

    // Example 2: Create 6 Posts each with 4 Comments (24 Comments total)
    PostFactory::createMany(6, ['comments' => CommentFactory::new()->many(4)]);

    // Example 3: Create 6 Posts each with between 0 and 10 Comments
    PostFactory::createMany(6, ['comments' => CommentFactory::new()->many(0, 10)]);

Many-to-Many
............

The following assumes the ``Post`` entity has a many-to-many relationship with ``Tag``:

.. code-block:: php

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

Lazy values
~~~~~~~~~~~

The ``getDefaults()`` method is called everytime a factory is instantiated (even if you don't end up
creating it). Sometimes, you might not want your value calculated every time. For example, if you have a value for one
of your attributes that:

 - has side effects (i.e. creating a file or fetching a random existing entity from another factory)
 - you only want to calculate once (i.e. creating an entity from another factory to pass as a value into multiple other factories)

You can wrap the value in a ``LazyValue`` which ensures the value is only calculated when/if it's needed. Additionally,
the LazyValue can be  `memoized <https://en.wikipedia.org/wiki/Memoization>`_ so that it is only calculated once.

    .. code-block:: php

        use Zenstruck\Foundry\Attributes\LazyValue;
        use function Zenstruck\Foundry\lazy;
        use function Zenstruck\Foundry\memoize;

        class TaskFactory extends ModelFactory
        {
            // ...

            protected function getDefaults(): array
            {
                $owner = LazyValue::memoize(fn () => UserFactory::new());

                return [
                    // Call CategoryFactory::random() everytime this factory is instantiated
                    'category' => LazyValue::new(fn() => CategoryFactory::random()),
                    // The same User instance will be both added to the Project and set as the Task owner
                    'project' => ProjectFactory::new(['users' => [$owner]]),
                    'owner'   => $owner,
                ];
            }

            protected static function getClass(): string
            {
                return Task::class;
            }
        }

.. tip::

    the ``lazy()`` and ``memoize()`` helper functions can also be used to create LazyValues,
    instead of ``LazyValue::new()`` and ``LazyValue::memoize()``.

Factories as Services
~~~~~~~~~~~~~~~~~~~~~

If your factories require dependencies, you can define them as a service. The following example demonstrates a very
common use-case: encoding a password with the ``UserPasswordHasherInterface`` service.

.. code-block:: php

    // src/Factory/UserFactory.php

    namespace App\Factory;

    use App\Entity\User;
    use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
    use Zenstruck\Foundry\ModelFactory;

    final class UserFactory extends ModelFactory
    {
        private $passwordHasher;

        public function __construct(UserPasswordHasherInterface $passwordHasher)
        {
            parent::__construct();

            $this->passwordHasher = $passwordHasher;
        }

        protected function getDefaults(): array
        {
            return [
                'email' => self::faker()->unique()->safeEmail(),
                'password' => '1234',
            ];
        }

        protected function initialize(): self
        {
            return $this
                ->afterInstantiate(function(User $user) {
                    $user->setPassword($this->passwordHasher->hashPassword($user, $user->getPassword()));
                })
            ;
        }

        protected static function getClass(): string
        {
            return User::class;
        }
    }

If using a standard Symfony Flex app, this will be autowired/autoconfigured. If not, register the service and tag
with ``foundry.factory``.

Use the factory as normal:

.. code-block:: php

    UserFactory::createOne(['password' => 'mypass'])->getPassword(); // "mypass" encoded
    UserFactory::createOne()->getPassword(); // "1234" encoded (because "1234" is set as the default password)

.. note::

    The provided bundle is required for factories as services.

.. note::

    If using ``make:factory --test``, factories will be created in the ``tests/Factory`` directory which is not
    autowired/autoconfigured in a standard Symfony Flex app. You will have to manually register these as
    services.

Anonymous Factories
~~~~~~~~~~~~~~~~~~~

Foundry can be used to create factories for entities that you don't have model factories for:

.. code-block:: php

    use App\Entity\Post;
    use function Zenstruck\Foundry\anonymous;
    use function Zenstruck\Foundry\create;
    use function Zenstruck\Foundry\create_many;
    use function Zenstruck\Foundry\repository;

    $factory = anonymous(Post::class);

    // has the same API as ModelFactory's
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
    $repository->all(); // Post[]|Proxy[] all the persisted Post's

    $repository->findBy(['author' => 'kevin']); // Post[]|Proxy[] matching the filter

    $repository->find(5); // Post|Proxy with the id of 5
    $repository->find(['title' => 'My First Post']); // Post|Proxy matching the filter

    // get a random object that has been persisted
    $repository->random(); // returns Post|Proxy
    $repository->random(['author' => 'kevin']); // filter by the passed attributes

    // get a random set of objects that have been persisted
    $repository->randomSet(4); // array containing 4 "Post|Proxy" objects
    $repository->randomSet(4, ['author' => 'kevin']); // filter by the passed attributes

    // random range of persisted objects
    $repository->randomRange(0, 5); // array containing 0-5 "Post|Proxy" objects
    $repository->randomRange(0, 5, ['author' => 'kevin']); // filter by the passed attributes

    // convenience functions
    $entity = create(Post::class, ['field' => 'value']);
    $entities = create_many(Post::class, 5, ['field' => 'value']);

.. note::

    If your anonymous factory code is getting too complex, this could be a sign you need an explicit model factory class.

Delay Flush
~~~~~~~~~~~

When creating/persisting many factories at once, it can be improve performance
to instantiate them all without saving to the database, then flush them all at
once. To do this, wrap the operations in a ``Factory::delayFlush()`` callback:

.. code-block:: php

    use Zenstruck\Foundry\Factory;

    Factory::delayFlush(function() {
        CategoryFactory::createMany(100); // instantiated/persisted but not flushed
        TagFactory::createMany(200); // instantiated/persisted but not flushed
    }); // single flush

.. _without-persisting:

Without Persisting
~~~~~~~~~~~~~~~~~~

Factories can also create objects without persisting them. This can be useful for unit tests where you just want to test
the behaviour of the actual object or for creating objects that are not entities. When created, they are still wrapped
in a ``Proxy`` to optionally save later.

.. code-block:: php

    use App\Factory\PostFactory;
    use App\Entity\Post;
    use Zenstruck\Foundry\anonymous;
    use function Zenstruck\Foundry\instantiate;
    use function Zenstruck\Foundry\instantiate_many;

    $post = PostFactory::new()->withoutPersisting()->create(); // returns Post|Proxy
    $post->setTitle('something else'); // do something with object
    $post->save(); // persist the Post (save() is a method on Proxy)

    $post = PostFactory::new()->withoutPersisting()->create()->object(); // actual Post object

    $posts = PostFactory::new()->withoutPersisting()->many(5)->create(); // returns Post[]|Proxy[]

    // anonymous factories:
    $factory = anonymous(Post::class);

    $entity = $factory->withoutPersisting()->create(['field' => 'value']); // returns Post|Proxy

    $entity = $factory->withoutPersisting()->create(['field' => 'value'])->object(); // actual Post object

    $entities = $factory->withoutPersisting()->many(5)->create(['field' => 'value']); // returns Post[]|Proxy[]

    // convenience functions
    $entity = instantiate(Post::class, ['field' => 'value']);
    $entities = instantiate_many(Post::class, 5, ['field' => 'value']);

If you'd like your model factory to not persist by default, override its ``initialize()`` method to add this behaviour:

.. code-block:: php

    protected function initialize(): self
    {
        return $this
            ->withoutPersisting()
        ;
    }

Now, after creating objects using this factory, you'd have to call ``->save()`` to actually persist them to the database.

.. tip::

    If you'd like to disable persisting by default for all your model factories:

    1. Create an abstract model factory that extends ``Zenstruck\Foundry\ModelFactory``.
    2. Override the ``initialize()`` method as shown above.
    3. Have all your model factories extend from this.

Using with DoctrineFixturesBundle
---------------------------------

Foundry works out of the box with `DoctrineFixturesBundle <https://symfony.com/bundles/DoctrineFixturesBundle/current/index.html>`_.
You can simply use your factories and stories right within your fixture files:

.. code-block:: php

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

Run the ``doctrine:fixtures:load`` as normal to seed your database.

Using in your Tests
-------------------

Traditionally, data fixtures are defined in one or more files outside of your tests. When writing tests using these
fixtures, your fixtures are a sort of a *black box*. There is no clear connection between the fixtures and what you
are testing.

Foundry allows each individual test to fully follow the `AAA <https://www.thephilocoder.com/unit-testing-aaa-pattern/>`_
("Arrange", "Act", "Assert") testing pattern. You create your fixtures using "factories" at the beginning of each test.
You only create fixtures that are applicable for the test. Additionally, these fixtures are created with only the
attributes required for the test - attributes that are not applicable are filled with random data. The created fixture
objects are wrapped in a "proxy" that helps with pre and post assertions.

Let's look at an example:

.. code-block:: php

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

.. code-block:: php

    use App\Factory\PostFactory;
    use Zenstruck\Foundry\Test\Factories;
    use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

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

.. code-block:: php

    use Zenstruck\Foundry\Test\Factories;
    use Zenstruck\Foundry\Test\ResetDatabase;
    use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

    class MyTest extends WebTestCase
    {
        use ResetDatabase, Factories;

        // ...
    }

Before the first test using the ``ResetDatabase`` trait, it drops (if exists) and creates the test database.
Then, by default, before each test, it resets the schema using ``doctrine:schema:drop``/``doctrine:schema:create``.

Alternatively, you can have it run your migrations instead by modifying the ``reset_mode`` option in configuration file.
When using this *mode*, before each test, the database is dropped/created and your migrations run (via
``doctrine:migrations:migrate``). This mode can really make your test suite slow (especially if you have a lot of
migrations). It is highly recommended to use `DamaDoctrineTestBundle`_ to improve the
speed. When this bundle is enabled, the database is dropped/created and migrated only once for the suite.

.. tip::

    Create a base TestCase for tests using factories to avoid adding the traits to every TestCase.

.. tip::

    If your tests :ref:`are not persisting <without-persisting>` the objects they create, these test traits are not
    required.

By default, ``ResetDatabase`` resets the default configured connection's database and default configured object manager's
schema. To customize the connection's and object manager's to be reset (or reset multiple connections/managers), use the
bundle's configuration:

.. configuration-block::

    .. code-block:: yaml

        # config/packages/zenstruck_foundry.yaml
        when@dev: # see Bundle Configuration section about sharing this in the test environment
            zenstruck_foundry:
                database_resetter:
                    orm:
                        connections:
                            - orm_connection_1
                            - orm_connection_2
                        object_managers:
                            - orm_object_manager_1
                            - orm_object_manager_2
                        reset_mode: schema
                    odm:
                        object_managers:
                            - odm_object_manager_1
                            - odm_object_manager_2

Object Proxy
~~~~~~~~~~~~

Objects created by a factory are wrapped in a special *Proxy* object. These objects allow your doctrine entities
to have `Active Record <https://en.wikipedia.org/wiki/Active_record_pattern>`_ *like* behavior:

.. code-block:: php

    use App\Factory\PostFactory;

    $post = PostFactory::createOne(['title' => 'My Title']); // instance of Zenstruck\Foundry\Proxy

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

Force Setting
.............

Object proxies have helper methods to access non-public properties of the object they wrap:

.. code-block:: php

    // set private/protected properties
    $post->forceSet('createdAt', new \DateTime());

    // get private/protected properties
    $post->forceGet('createdAt');

Auto-Refresh
............

Object proxies have the option to enable *auto refreshing* that removes the need to call ``->refresh()`` before calling
methods on the underlying object. When auto-refresh is enabled, most calls to proxy objects first refresh the wrapped
object from the database.

.. code-block:: php

    use App\Factory\PostFactory;

    $post = PostFactory::new(['title' => 'Original Title'])
        ->create()
        ->enableAutoRefresh()
    ;

    // ... logic that changes the $post title to "New Title" (like your functional test)

    $post->getTitle(); // "New Title" (equivalent to $post->refresh()->getTitle())

Without auto-refreshing enabled, the above call to ``$post->getTitle()`` would return "Original Title".

.. note::

    A situation you need to be aware of when using auto-refresh is that all methods refresh the object first. If
    changing the object's state via multiple methods (or multiple force-sets), an "unsaved changes" exception will be
    thrown:

    .. code-block:: php

        use App\Factory\PostFactory;

        $post = PostFactory::new(['title' => 'Original Title', 'body' => 'Original Body'])
            ->create()
            ->enableAutoRefresh()
        ;

        $post->setTitle('New Title');
        $post->setBody('New Body'); // exception thrown because of "unsaved changes" to $post from above

    To overcome this, you need to first disable auto-refreshing, then re-enable after making/saving the changes:

    .. code-block:: php

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

.. note::

    You can enable/disable auto-refreshing globally to have every proxy auto-refreshable by default or not. When
    enabled, you will have to *opt-out* of auto-refreshing.

    .. configuration-block::

        .. code-block:: yaml

            # config/packages/zenstruck_foundry.yaml
            when@dev: # see Bundle Configuration section about sharing this in the test environment
                zenstruck_foundry:
                    auto_refresh_proxies: true/false

Repository Proxy
~~~~~~~~~~~~~~~~

This library provides a *Repository Proxy* that wraps your object repositories to provide useful assertions and methods:

.. code-block:: php

    use App\Entity\Post;
    use App\Factory\PostFactory;
    use function Zenstruck\Foundry\repository;

    // instance of RepositoryProxy that wraps PostRepository
    $repository = PostFactory::repository();

    // alternative to above for proxying repository you haven't created model factories for
    $repository = repository(Post::class);

    // helpful methods - all returned object(s) are proxied
    $repository->inner(); // the real "wrapped" repository
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

Assertions
~~~~~~~~~~

Both object proxies and your ModelFactory have helpful PHPUnit assertions:

.. code-block:: php

    use App\Factory\PostFactory;

    $post = PostFactory::createOne();

    $post->assertPersisted();
    $post->assertNotPersisted();

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
values are: stories as service, "global" stories and invokable services.

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

PHPUnit Data Providers
~~~~~~~~~~~~~~~~~~~~~~

It is possible to use factories in
`PHPUnit data providers <https://phpunit.readthedocs.io/en/9.3/writing-tests-for-phpunit.html#data-providers>`_:

.. code-block:: php

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

    Be sure your data provider returns only instances of ``ModelFactory`` and you do not try to call ``->create()`` on them.
    Data providers are computed early in the phpunit process before Foundry is booted.

.. note::

    For the same reason as above, it is not possible to use `Factories as Services`_ with required
    constructor arguments (the container is not yet available).

.. note::

    Still for the same reason, if `Faker`_ is needed along with ``->withAttributes()`` within a data provider, you'll need
    to pass attributes as a *callable*.

    Given the data provider of the previous example, here is ``PostFactory::published()``

    .. code-block:: php

        public function published(): self
        {
            // This won't work in a data provider!
            // return $this->withAttributes(['published_at' => self::faker()->dateTime()]);

            // use this instead:
            return $this->withAttributes(
                static fn() => [
                    'published_at' => self::faker()->dateTime()
                ]
            );
        }

.. tip::

    ``ModelFactory::new()->many()`` and ``ModelFactory::new()->sequence()`` return a special ``FactoryCollection`` object
    which can be used to generate data providers:

    .. code-block:: php

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

    .. code-block:: php

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

Miscellaneous
.............

1. Disable debug mode when running tests. In your ``.env.test`` file, you can set ``APP_DEBUG=0`` to have your tests
   run without debug mode. This can speed up your tests considerably. You will need to ensure you cache is cleared
   before running the test suite. The best place to do this is in your ``tests/bootstrap.php``:

   .. code-block:: php

       // tests/bootstrap.php
       // ...
       if (false === (bool) $_SERVER['APP_DEBUG']) {
           // ensure fresh cache
           (new Symfony\Component\Filesystem\Filesystem())->remove(__DIR__.'/../var/cache/test');
       }

2. Reduce password encoder *work factor*. If you have a lot of tests that work with encoded passwords, this will cause
   these tests to be unnecessarily slow. You can improve the speed by reducing the *work factor* of your encoder:

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

3. Pre-encode user passwords with a known value via ``bin/console security:encode-password`` and set this in
   ``ModelFactory::getDefaults()``. Add the known value as a ``const`` on your factory:

   .. code-block:: php

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

   Now, in your tests, when you need access to the unencoded password for a user created with ``UserFactory``, use
   ``UserFactory::DEFAULT_PASSWORD``.

Non-Kernel Tests
~~~~~~~~~~~~~~~~

Foundry can be used in standard PHPUnit unit tests (TestCase's that just extend ``PHPUnit\Framework\TestCase`` and not
``Symfony\Bundle\FrameworkBundle\Test\KernelTestCase``). These tests still require using the ``Factories`` trait to boot
Foundry but will not have doctrine available. Factories created in these tests will not be persisted (calling
``->withoutPersisting()`` is not necessary). Because the bundle is not available in these tests,
any bundle configuration you have will not be picked up.

.. code-block:: php

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

.. code-block:: php

    // tests/bootstrap.php
    // ...

    Zenstruck\Foundry\Test\TestState::configure(
        instantiator: (new Zenstruck\Foundry\Instantiator())
            ->withoutConstructor()
            ->allowExtraAttributes()
            ->alwaysForceProperties(),
        faker: Faker\Factory::create('fr_FR')
    );

.. note::

    `Factories as Services`_ and `Stories as Services`_ with required
    constructor arguments are not usable in non-Kernel tests. The container is not available to resolve their dependencies.
    The easiest work-around is to make the test an instance of ``Symfony\Bundle\FrameworkBundle\Test\KernelTestCase`` so the
    container is available.

.. _stories:

Stories
-------

Stories are useful if you find your test's *arrange* step is getting complex (loading lots of fixtures) or duplicating
logic between tests and/or your dev fixtures. They are used to extract a specific database *state* into a *story*.
Stories can be loaded in your fixtures and in your tests, they can also depend on other stories.

Create a story using the maker command:

.. code-block:: terminal

    $ bin/console make:story Post

.. note::

    Creates ``PostStory.php`` in ``src/Story``, add ``--test`` flag to create in ``tests/Story``.

Modify the *build* method to set the state for this story:

.. code-block:: php

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

.. code-block:: php

    PostStory::load(); // loads the state defined in PostStory::build()

    PostStory::load(); // does nothing - already loaded

.. note::

    Objects persisted in stories are cleared after each test (unless it is a
    :ref:`Global State Story <global-state>`).

Stories as Services
~~~~~~~~~~~~~~~~~~~

If your stories require dependencies, you can define them as a service:

.. code-block:: php

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

If using a standard Symfony Flex app, this will be autowired/autoconfigured. If not, register the service and tag
with ``foundry.story``.

.. note::

    The provided bundle is required for stories as services.

Story State
~~~~~~~~~~~

Another feature of *stories* is the ability for them to *remember* the objects they created to be referenced later:

.. code-block:: php

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

.. code-block:: php

    PostFactory::createOne(['category' => CategoryStory::get('php')]);

    // or use the magic method (functionally equivalent to above)
    PostFactory::createOne(['category' => CategoryStory::php()]);

.. tip::

    Unlike factories, stories are not tied to a specific type, and then they cannot be generic, but you can leverage
    the magic method and PHPDoc to improve autocompletion and fix static analysis issues with stories:

    .. code-block:: php

        // src/Story/CategoryStory.php

        namespace App\Story;

        use App\Factory\CategoryFactory;
        use Zenstruck\Foundry\Story;

        /**
         * @method static Category php()
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

.. code-block:: php

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

.. code-block:: php

    ProvinceStory::getRandom('be'); // random Province|Proxy from "be" pool
    ProvinceStory::getRandomSet('be', 3); // 3 random Province|Proxy's from "be" pool
    ProvinceStory::getRandomRange('be', 1, 4); // between 1 and 4 random Province|Proxy's from "be" pool
    ProvinceStory::getPool('be'); // all Province|Proxy's from "be" pool

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

.. configuration-block::

    .. code-block:: yaml

        zenstruck_foundry:

            # Whether to auto-refresh proxies by default (https://github.com/zenstruck/foundry#auto-refresh)
            auto_refresh_proxies: true

            # Configure faker to be used by your factories.
            faker:

                # Change the default faker locale.
                locale:               null # Example: fr_FR

                # Random number generator seed to produce the same fake values every run
                seed:                 null # Example: 1234

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

            # Configure the database reset mechanism
            database_resetter:

                # Config related to ORM
                orm:

                    # Connections to reset. If empty, the default connection is used.
                    connections: []

                    # Object managers to reset. If empty, the default manager is used.
                    object_managers: []

                    # Whether to use doctrine:schema:update or migrations when resetting schema.
                    reset_mode: schema # "schema" or "migrate"

                # Config related to ODM
                odm:

                    # Object managers to reset. If empty, the default manager is used.
                    object_managers: []

            # Add global state.
            global_state: []

            # Configure Foundry's make:factory command
            make_factory:

                # Namespace to use for make:factory. Is overridden by --namespace option
                default_namespace: 'Factory'

    .. code-block:: php

        $config->extension('zenstruck_foundry', [

            // Whether to auto-refresh proxies by default (https://github.com/zenstruck/foundry#auto-refresh)
            'auto_refresh_proxies' => false,

            // Configure faker to be used by your factories.
            'faker' => [

                // Change the default faker locale.
                'locale' => null,

                // Random number generator seed to produce the same fake values every run
                'seed' => null,

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

            // Configure the database reset mechanism
            'database_resetter' => [

                // Config related to ORM
                'orm' => [

                    // Connections to reset. If empty, the default connection is used.
                    'connections' => [],

                    // Whether or not to allow extra attributes.
                    'object_managers' => false,

                    // Whether to use doctrine:schema:update or migrations when resetting schema.
                    'reset_mode' => 'schema', // 'schema' or 'migration'
                ],

                // Config related to ODM
                'odm' => [

                    // Whether or not to allow extra attributes.
                    'object_managers' => false,
                ],

            ],

            // Add global state
            'global_state' => [],

            // Configure Foundry's make:factory command
            'make_factory' => [

                // Namespace to use for make:factory. Is overridden by --namespace option
                'default_namespace' => 'Factory'
            ]
        ]);
