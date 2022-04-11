# Foundry

[![CI Status](https://github.com/zenstruck/foundry/workflows/CI/badge.svg)](https://github.com/zenstruck/foundry/actions?query=workflow%3ACI)
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

The factories can be used inside [DoctrineFixturesBundle](https://symfony.com/bundles/DoctrineFixturesBundle/current/index.html)
to load fixtures or inside your tests, [where it has even more features](https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#using-in-your-tests).

Foundry supports `doctrine/orm` (with [doctrine/doctrine-bundle](https://github.com/doctrine/doctrinebundle)),
`doctrine/mongodb-odm` (with [doctrine/mongodb-odm-bundle](https://github.com/doctrine/DoctrineMongoDBBundle))
or a combination of these.

Want to watch a screencast 🎥 about it? Check out https://symfonycasts.com/foundry

**[Read the Documentation](https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html)**

## Credit

The [AAA](https://www.thephilocoder.com/unit-testing-aaa-pattern/) style of testing was first introduced to me by
[Adam Wathan's](https://adamwathan.me/) excellent [Test Driven Laravel Course](https://course.testdrivenlaravel.com/).
The inspiration for this libraries API comes from [Laravel factories](https://laravel.com/docs/master/database-testing)
and [christophrumpel/laravel-factories-reloaded](https://github.com/christophrumpel/laravel-factories-reloaded).
