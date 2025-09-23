# Migration guide from Foundry 2.8 to 2.9

The main feature of Foundry 2.9 is the deprecation of the `ResetDatabase` trait, in favor of a `#[ResetDatabase]` attribute,
along with the [PHPUnit extension](https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#phpunit-extension)
shipped by Foundry. 

The trait will be removed in Foundry 3.0, and the usage of the attribute will be mandatory to reset the database in your tests.

> [!WARNING]
> The PHPUnit extension mechanism was introduced in PHPUnit 10. This means that Foundry 3 won't be compatible 
> with PHPUnit 9 anymore (but Foundry 2 will remain compatible with PHPUnit 9).

## How to

> [!IMPORTANT]
> If you're still not using PHPUnit 10 or grater, there is nothing to do (yet!)

Enable Foundry's [PHPUnit extension](https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#phpunit-extension)
in your `phpunit.xml` file:

```xml
<phpunit>
    <extensions>
        <bootstrap class="Zenstruck\Foundry\PHPUnit\FoundryExtension"/>
    </extensions>
</phpunit>
```

And then, replace all the `use ResetDatabase;` statements by a `#[\Zenstruck\Foundry\Attribute\ResetDatabase]` attribute
on your test classes. Note that you can put the attribute on a parent class, it will be inherited by all its children.

## Rector rules

A Rector set is available to automatically replace the trait by the attribute in all your tests.

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
