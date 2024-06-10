# Migration guide from Foundry 1.x to 2.0

Foundry 2 has changed some of its API.
The global philosophy is still the same.
The main change is that we've introduced a separation between "object" factories,
"persistence" factories and "persistence with proxy" factories.

While Foundry 1.x was "persistence first", Foundry 2 is "object first".
This would allow more decoupling from the persistence layer.

## How to

Every modification needed for a 1.x to 2.0 migration is covered by a deprecation.
You'll need to upgrade to the latest 1.x version, activate the deprecation helper,
make the tests run, and fix all the deprecations reported.

Here is an example of how the deprecation helper can be activated.
You should set the `SYMFONY_DEPRECATIONS_HELPER` variable in `phpunit.xml` or `.env.local` file:
```shell
SYMFONY_DEPRECATIONS_HELPER="max[self]=0&amp;max[direct]=0&amp;quiet[]=indirect&amp;quiet[]=other"
```

> [!IMPORTANT]
> Some deprecations can also be sent during compilation step.
> These deprecations can be displayed by using the command: `$ bin/console debug:container --deprecations`

> [!WARNING]
> If using PHPUnit 10 or above, Symfony's PHPUnit bridge is not compatible, and configuring `SYMFONY_DEPRECATIONS_HELPER`
> is not useful. Instead, you'll need to add `ignoreSuppressionOfDeprecations=true` to the `<source>` tag in `phpunit.xml`
> and run PHPUnit with the `--display-deprecations` option.

> [!TIP]
> PHPStan plugin [`phpstan/phpstan-deprecation-rules`](https://github.com/phpstan/phpstan-deprecation-rules) might also
> be useful to detect the remaining deprecations.

## Rector rules

In the latest 1.x version, you'll find [rector rules](https://getrector.org/) which will help with the migration path.

First, you'll need `rector/rector` and `phpstan/phpstan-doctrine`:
```shell
composer require --dev rector/rector phpstan/phpstan-doctrine
```

Then, create a `rector.php` file:

```php
<?php

use Rector\Config\RectorConfig;
use Zenstruck\Foundry\Utils\Rector\FoundrySetList;

return RectorConfig::configure()
    ->withPaths([
        // add all paths where your factories are defined and where Foundry is used
        'src/Factory'
        'tests'
    ])
    ->withSets([FoundrySetList::UP_TO_FOUNDRY_2])
;
```

And finally, run Rector:
```shell
# you can run Rector in "dry run" mode, in order to see which files will be modified
vendor/bin/rector process --dry-run

# actually modify files
vendor/bin/rector process
```

> [!IMPORTANT]
> Rector rules may not totally cover all deprecations (some complex cases may not be handled)
> You'd still need to run the tests with deprecation helper enabled to ensure everything is fixed
> and then fix all deprecations left.

> [!TIP]
> You can try to run these rules twice. Sometimes, the second run will find differences that it could not spot on
> the first run.

> [!NOTE]
> Once you've finished the migration to 2.0, it is not necessary to keep the Foundry rule set in your Rector
> config.

### Doctrine's mapping

Rector rules need to understand your Doctrine mapping to guess which one of `PersistentProxyObjectFactory` or
`ObjectFactory` it should use.

If your mapping is defined in the code thanks to attributes or annotations, everything is OK.
If the mapping is defined outside the code, with xml, yaml or php configuration, some extra work is needed:

1. Create a `tests/object-manager.php` file which will expose your doctrine config. Here is an example:
```php
use App\Kernel;
use Symfony\Component\Dotenv\Dotenv;

require __DIR__ . '/../vendor/autoload.php';

(new Dotenv())->bootEnv(__DIR__ . '/../.env');

$kernel = new Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);
$kernel->boot();
return $kernel->getContainer()->get('doctrine')->getManager();
```

2. Set this file path in Rector's config:

```php
<?php

use Rector\Config\RectorConfig;
use Zenstruck\Foundry\Utils\Rector\PersistenceResolver;
use Zenstruck\Foundry\Utils\Rector\FoundrySetList;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->paths(['tests']); // add all paths where Foundry is used
    $rectorConfig->sets([FoundrySetList::UP_TO_FOUNDRY_2]);
    $rectorConfig->singleton(
        PersistenceResolver::class,
        static fn() => new PersistenceResolver(__DIR__ . '/tests/object-manager.php')
    );
};
```

## Known BC breaks

The following error cannot be covered by Rector rules nor by the deprecation layer.
```
RefreshObjectFailed: Cannot auto refresh "[Entity FQCN]" as there are unsaved changes.
Be sure to call ->_save() or disable auto refreshing.
```

This is an auto-refresh problem, where the "proxified" object is being accessed after modification.
You'll find some [documentation](https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#auto-refresh)
about it.

To mitigate this problem, you should either stop using a proxy object, or wrap the modifications in the method
`->_withoutAutoRefresh()`.

```diff
$proxyPost = PostProxyFactory::createOne();
- $proxyPost->setTitle();
- $proxyPost->setBody(); // 💥
+$proxyPost->_withoutAutoRefresh(
+   function(Post $object) {
+       $proxyPost->setTitle();
+       $proxyPost->setBody();
+   }
+);
```


## Deprecations list

Here is the full list of modifications needed:

### Factory

- `withAttributes()` and `addState()` are both deprecated in favor of `with()`
- `sequence()` and `createSequence()` do not accept `callable` as a parameter anymore

#### Change factories' base class

`Zenstruck\Foundry\ModelFactory` is now deprecated.
You should choose between:
- `\Zenstruck\Foundry\ObjectFactory`: creates not-persistent plain objects,
- `\Zenstruck\Foundry\Persistence\PersistentObjectFactory`: creates and stores persisted objects, and directly return them,
- `\Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory`: same as above, but returns a "proxy" version of the object.
  This last class basically acts the same way as the old `ModelFactory`.

As a rule of thumb to help you choose between these two new factory parent classes:
- using `ObjectFactory` is straightforward: if the object cannot be persisted, you must use this one
- only entities (ORM) or documents (ODM) should use `PersistentObjectFactory` or `PersistentProxyObjectFactory`
- you should only use `PersistentProxyObjectFactory` if you want to leverage "auto refresh" behavior

> [!WARNING]
> Neither `PersistentObjectFactory` or `PersistentProxyObjectFactory` should be chosen to create not persistent objects.
> This will throw a deprecation in 1.x and will create an error in 2.0

> [!IMPORTANT]
> Since `PersistentObjectFactory` does not return a `Proxy` anymore, you'll have to remove all calls to `->object()`
> or any other proxy method on object created by this type of factory.

> [!NOTE]
> You will have to change some methods prototypes in your classes:

```php
// before
protected function getDefaults(): array
{
    // ...
}

// after
protected function defaults(): array|callable
{
    // ...
}
```

```php
// before
protected static function getClass(): string
{
    // ...
}

// after
public static function class(): string
{
    // ...
}
```

```php
// before
protected function initialize()
{
    // ...
}

// after
protected function initialize(): static
{
    // ...
}
```

### Proxy

Foundry 2.0 will completely change how the `Proxy` system works, by leveraging Symfony's lazy proxy mechanism.
`Proxy` will no longer be a wrapper class, but a "real" proxy, meaning your objects will be of the desired class AND `Proxy` object.
This implies that calling `->object()` (or, now, `_real()`) everywhere to satisfy the type system won't be needed anymore!

`Proxy` class comes with deprecations as well:
- replace everywhere you're type-hinting `Zenstruck\Foundry\Proxy` to the interface `Zenstruck\Foundry\Persistence\Proxy`
- most of `Proxy` methods are deprecated:
  - `object()` -> `_real()`
  - `save()` -> `_save()`
  - `remove()` -> `_delete()`
  - `refresh()` -> `_refresh()`
  - `forceSet()` -> `_set()`
  - `forceGet()` -> `_get()`
  - `repository()` -> `_repository()`
  - `enableAutoRefresh()` -> `_enableAutoRefresh()`
  - `disableAutoRefresh()` -> `_disableAutoRefresh()`
  - `withoutAutoRefresh()` -> `_withoutAutoRefresh()`
  - `isPersisted()` is removed without any replacement
  - `forceSetAll()` is removed without any replacement
  - `assertPersisted()` is removed without any replacement
  - `assertNotPersisted()` is removed without any replacement
- Everywhere you've type-hinted `Zenstruck\Foundry\FactoryCollection<T>` which was coming from a `PersistentProxyObjectFactory`, replace to `Zenstruck\Foundry\FactoryCollection<Proxy<T>>`

### Instantiator

- `Zenstruck\Foundry\Instantiator` class is deprecated in favor of `\Zenstruck\Foundry\Object\Instantiator`. You should change them everywhere.
- `new Instantiator()` is deprecated: use `Instantiator::withConstructor()` or `Instantiator::withoutConstructor()` depending on your needs.
- `Instantiator::alwaysForceProperties()` is deprecated in favor of `Instantiator::alwaysForce()`. Be careful of the modification of the parameter which is now a variadic.
- `Instantiator::allowExtraAttributes()` is deprecated in favor of `Instantiator::allowExtra()`. Be careful of the modification of the parameter which is now a variadic.
- Configuration `zenstruck_foundry.without_constructor` is deprecated in favor of `zenstruck_foundry.use_constructor`

### Standalone functions

- `Zenstruck\Foundry\create()` -> `Zenstruck\Foundry\Persistence\persist()`
- `Zenstruck\Foundry\instantiate()` -> `Zenstruck\Foundry\object()`
- `Zenstruck\Foundry\repository()` -> `Zenstruck\Foundry\Persistence\repository()`
- `Zenstruck\Foundry\Factory::delayFlush()` -> `Zenstruck\Foundry\Persistence\flush_after()`
- Usage of any method in `Zenstruck\Foundry\Test\TestState` should be replaced by `Zenstruck\Foundry\Test\UnitTestConfig::configure()`
- `Zenstruck\Foundry\instantiate_many()` is removed without any replacement
- `Zenstruck\Foundry\create_many()` is removed without any replacement

### Trait `Factories`
- `Factories::disablePersist()` -> `Zenstruck\Foundry\Persistence\disable_persisting()`
- `Factories::enablePersist()` -> `Zenstruck\Foundry\Persistence\enable_persisting()`
- both `disablePersist()` and `enable_persisting()` should not be called when Foundry is booted without Doctrine (ie: in a unit test)

### Bundle configuration

Here is a diff of the bundle's configuration, all configs in red should be migrated to the green ones:

```diff
zenstruck_foundry:
-    auto_refresh_proxies: null
    instantiator:
-        without_constructor:  false
+        use_constructor:  true
+    orm:
+        auto_persist:         true
+        reset:
+            connections: [default]
+            entity_managers: [default]
+            mode: schema
+    mongo:
+        auto_persist:         true
+        reset:
+            document_managers: [default]
-    database_resetter:
-        enabled:              true
-        orm:
-            connections:          []
-            object_managers:      []
-            reset_mode:           schema
-        odm:
-            object_managers:      []
```

### Misc.
- type-hinting to `Zenstruck\Foundry\RepositoryProxy` should be replaced by `Zenstruck\Foundry\Persistence\RepositoryDecorator`
- type-hinting to `Zenstruck\Foundry\RepositoryAssertions` should be replaced by `Zenstruck\Foundry\Persistence\RepositoryAssertions`
- Methods in `Zenstruck\Foundry\RepositoryProxy` do not return `Proxy` anymore, but they return the actual object
