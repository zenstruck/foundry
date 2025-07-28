# Migration guide from Foundry 2.6 to 2.7

Foundry 2.7 provides a new way to auto-refresh entities, which leverages new [PHP 8.4 lazy objects](https://www.php.net/manual/en/language.oop5.lazy-objects.php)!

This means that the [`Proxy` mechanism](https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#object-proxy)
and all related classes and functions are deprecated. This change was made to fix a lot of quirks involved by the proxy mechanism,
and to reduce the maintenance burden of this feature.

## How to

> [!IMPORTANT]
> The new auto-refresh mechanism only applies for PHP 8.4,
> if you're still not using PHP 8.4, there is nothing to do (yet!)

First, you need to configure whether you want to use the new auto-refresh mechanism:

```yaml
zenstruck_foundry:
    # from Foundry 2.7, with PHP >=8.4, not setting this configuration is deprecated
    enable_auto_refresh_with_lazy_objects: true
```

In both cases, you'll need to migrate your factories and code to remove all the `Proxy`-related code:
- remove all `_real()` calls
- more generally, replace all proxy methods, by their [function equivalent](https://github.com/zenstruck/foundry/blob/2.x/src/Persistence/functions.php)
- replace `PersistentProxyObjectFactory` to `PersistentObjectFactory`
- remove all types and PHPDoc related to proxies

Every modification needed for the migration is covered by a deprecation.
You'll need to upgrade to the 2.7 version, run the tests, and fix all the deprecations reported.

## Rector rules

This could be a lot of work in big projects, so we provide a [Rector rule set](https://getrector.org/) to help you with this migration.

First, you'll need to install `rector/rector`:
```shell
composer require --dev rector/rector
```

Then, create a `rector.php` file:

```php
<?php

use Rector\Config\RectorConfig;
use Zenstruck\Foundry\Utils\Rector\FoundrySetList;

return RectorConfig::configure()
    ->withPaths([
        // add all paths where your factories are defined and where Foundry is used
        'src',
        'tests'
    ])
    ->withSets([FoundrySetList::REMOVE_PROXIES])
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
> You'd still need to run the tests to ensure everything is fixed and no more deprecation are reported.

> [!TIP]
> You can try to run these rules twice with `--clear-cache` option. Sometimes, the second run will find differences
> that it could not spot on the first run.

> [!NOTE]
> Once you've finished the migration to 2.7, it is not necessary to keep the Foundry rule set in your Rector
> config.

## Deprecations list

Here is the full list of modifications needed:

- Change the base class of your factories from `PersistentProxyObjectFactory` to `PersistentObjectFactory`
You'll also need to update the PHPDoc `@extends` annotation to use the new class (covered by `ChangeFactoryBaseClassRector`).
- Remove all `_real()`, `_enableAutoRefresh()` and `_disableAutoRefresh()` calls (covered by `RemoveMethodCallRector`).
- Remove all `\Zenstruck\Foundry\Persistence\proxy()` and `\Zenstruck\Foundry\Persistence\proxy()` calls (covered by `RemoveFunctionCallRector`).
- Remove all `_withoutAutoRefresh()` calls (covered by `RemoveWithoutAutorefreshCallRector`).
- Replace some method calls on `Proxy` class with their function equivalent (covered by `MethodCallToFuncCallWIthObjectAsFirstParameterRector`):
  - `_get()` => `\Zenstruck\Foundry\Persistence\get()`
  - `_set()` => `\Zenstruck\Foundry\Persistence\set()`
  - `_save()` => `\Zenstruck\Foundry\Persistence\save()`
  - `_refresh()` => `\Zenstruck\Foundry\Persistence\refresh()`
  - `_delete()` => `\Zenstruck\Foundry\Persistence\delete()`
  - `_assertPersisted()` => `\Zenstruck\Foundry\Persistence\assert_persisted()`
  - `_assertNotPersisted()` => `\Zenstruck\Foundry\Persistence\assert_not_persisted()`

> [!NOTE]
> Calls to `Proxy::refresh()` also need to be replaced, but they are not covered by a Rector rule.

- Remove `Proxy` type for parameters in the prototype in methods and functions (covered by `ChangeProxyParamTypesRector`).
- Remove `Proxy` return type in methods and functions (covered by `ChangeProxyReturnTypesRector`).
- Remove all `Proxy` type hints in PHPDoc (covered by `RemovePhpDocProxyTypeHintRector`).
