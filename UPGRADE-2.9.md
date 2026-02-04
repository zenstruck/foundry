# Migration guide from Foundry 2.8 to 2.9

The main feature of Foundry 2.9 is the deprecation of the `Factories` and `ResetDatabase` traits, in favor of the [PHPUnit extension](https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#phpunit-extension)
shipped by Foundry. It was necessary to remember to add the traits in every test class. And in some cases, Foundry could
still work even if the trait wasn’t added to the test, which could lead to subtle bugs. Now, Foundry is globally enabled
once for all.

The trait will be removed in Foundry 3.0, and the extension will be mandatory.

> [!WARNING]
> The PHPUnit extension mechanism was introduced in PHPUnit 10. This means that Foundry 3 won't be compatible 
> with PHPUnit 9 anymore (but Foundry 2 will remain compatible with PHPUnit 9).

## How to

> [!IMPORTANT]
> If you're still not using PHPUnit 10 or grater, there is nothing to do (yet!)

Enable Foundry's [PHPUnit extension](https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#phpunit-extension):

```xml
<!-- phpunit.xml -->
<phpunit>
    <extensions>
        <bootstrap class="Zenstruck\Foundry\PHPUnit\FoundryExtension"/>
    </extensions>
</phpunit>
```

And then:
- remove all the `use Factories;` statements from your factories.
- replace all the `use ResetDatabase;` statements by a `#[\Zenstruck\Foundry\Attribute\ResetDatabase]` attribute
  on your test classes. Note that you can put the attribute on a parent class, it will be inherited by all its children.

### Automatic Database Reset for Base Test Classes

Instead of adding the `#[ResetDatabase]` attribute to every test class, you can configure Foundry to
automatically reset the database for all tests extending `Symfony\Bundle\FrameworkBundle\Test\KernelTestCase`:

```xml
<!-- phpunit.xml -->
<phpunit>
    <extensions>
        <bootstrap class="Zenstruck\Foundry\PHPUnit\FoundryExtension">
            <parameter name="enabled-auto-reset" value="true"/>
        </bootstrap>
    </extensions>
</phpunit>
```

## Rector rules

A Rector set is available to automatically remove the usage of the trait in all your tests.

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
    ->withPaths(['tests'])
    ->withSets([FoundrySetList::FOUNDRY_2_9])
;
```
