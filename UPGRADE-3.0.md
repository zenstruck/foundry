# Migration guide from Foundry 2.x to 3.0

## Before upgrading

Upgrade to the latest Foundry 2.x version, run your tests, and fix all the deprecations reported:
every feature removed in 3.0 is covered by a deprecation in 2.x.

Foundry 2.x ships [Rector](https://getrector.org/) sets for the biggest migrations,
those of [Foundry 2.7](https://github.com/zenstruck/foundry/blob/2.x/UPGRADE-2.7.md) (proxies)
and [Foundry 2.9](https://github.com/zenstruck/foundry/blob/2.x/UPGRADE-2.9.md) (PHPUnit extension).
Foundry 3.0 does not ship them anymore, so run them before upgrading:

```shell
composer require --dev rector/rector
```

```php
<?php
// rector.php

use Rector\Config\RectorConfig;
use Zenstruck\Foundry\Utils\Rector\FoundrySetList;

return RectorConfig::configure()
    ->withPaths([
        // add all paths where your factories are defined and where Foundry is used
        'src',
        'tests',
    ])
    ->withSets([FoundrySetList::FOUNDRY_2_7, FoundrySetList::FOUNDRY_2_9])
;
```

```shell
# you can run Rector in "dry run" mode, in order to see which files will be modified
vendor/bin/rector process --dry-run

# actually modify files
vendor/bin/rector process
```

> [!IMPORTANT]
> Rector rules may not cover all deprecations (some complex cases may not be handled).
> Run the tests afterwards to ensure no more deprecations are reported.

## Requirements

Foundry 3.0 requires PHP 8.4 or greater and Symfony 8.0 or greater.

It conflicts with the following versions of the packages it integrates with:

| Package                     | Minimum version |
| --------------------------- | --------------- |
| `doctrine/orm`              | 3.0             |
| `dama/doctrine-test-bundle` | 8.0             |
| `phpunit/phpunit`           | 11.0            |

Foundry handles the objects created in data providers only from PHPUnit 11.4.

## Proxies are removed

`Proxy`, `PersistentProxyObjectFactory` and all the proxy-related methods and functions are removed,
in favor of auto-refresh with [PHP 8.4 lazy objects](https://www.php.net/manual/en/language.oop5.lazy-objects.php),
which is now enabled by default (`zenstruck_foundry.enable_auto_refresh_with_lazy_objects: true`).

See [UPGRADE-2.7.md](https://github.com/zenstruck/foundry/blob/2.x/UPGRADE-2.7.md) for the full list of changes,
all covered by the `FOUNDRY_2_7` Rector set.

The `--with-phpdoc` option of `make:factory` is removed too.

## The PHPUnit extension is mandatory

The `Factories` and `ResetDatabase` traits are removed:
enable Foundry's [PHPUnit extension](https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#phpunit-extension)
and replace `use ResetDatabase;` with the `#[\Zenstruck\Foundry\Attribute\ResetDatabase]` attribute.

```xml
<!-- phpunit.xml -->
<phpunit>
    <extensions>
        <bootstrap class="Zenstruck\Foundry\PHPUnit\FoundryExtension"/>
    </extensions>
</phpunit>
```

See [UPGRADE-2.9.md](https://github.com/zenstruck/foundry/blob/2.x/UPGRADE-2.9.md) for details,
all covered by the `FOUNDRY_2_9` Rector set.

## Objects are always flushed once

Foundry always behaves as with `zenstruck_foundry.persistence.flush_once: true` in 2.x:
a factory call that creates several objects flushes them all at once.
The option is deprecated and has no effect anymore.

## Stories declared on a test class are built once per class

With [DAMADoctrineTestBundle](https://github.com/dmaicher/doctrine-test-bundle) 8.8 or greater and its PHPUnit extension,
a `#[WithStory]` declared on a test class is built once, before the first test of the class, instead of before each test.
Each test is still rolled back to the state right after the story was built.
The same goes for a `#[WithStory]` declared on a test method with a data provider: it is built once for all its data sets.

What a story's `build()` method does outside of the database (static properties, files, calls to external services...)
now only happens once per test class.
