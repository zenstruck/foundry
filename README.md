# Foundry

[![CI Status](https://github.com/zenstruck/foundry/workflows/CI/badge.svg)](https://github.com/zenstruck/foundry/actions?query=workflow%3ACI)
[![Code Coverage](https://codecov.io/gh/zenstruck/foundry/branch/master/graph/badge.svg?token=77JIFYSUC5)](https://codecov.io/gh/zenstruck/foundry)
[![Latest Version](https://img.shields.io/packagist/v/zenstruck/foundry.svg)](https://packagist.org/packages/zenstruck/foundry)
[![Downloads](https://img.shields.io/packagist/dm/zenstruck/foundry)](https://packagist.org/packages/zenstruck/foundry)

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

## How to contribute

### Running the Test Suite

The test suite of this library needs one or more databases, then it comes with a docker compose configuration.

> [!NOTE]
> Docker and PHP installed locally (with `mysql`, `pgsql` & `mongodb` extensions) is required.

You can start the containers and run the test suite:

```bash
# start the container
$ docker compose up --detach

# install dependencies
$ composer update

# run test suite with all available permutations
$ composer test

# run only one permutation
$ vendor/bin/phpunit

# run test suite with dama/doctrine-test-bundle
$ vendor/bin/phpunit -c phpunit.dama.xml.dist

# run test suite with postgreSQL instead of MySQL
$ DATABASE_URL="postgresql://zenstruck:zenstruck@127.0.0.1:5433/zenstruck_foundry?serverVersion=15" vendor/bin/phpunit
```

### Overriding the default configuration

You can override default environment variables by creating a `.env.local` file, to easily enable permutations:

```bash
# .env.local
DATABASE_URL="postgresql://zenstruck:zenstruck@127.0.0.1:5433/zenstruck_foundry?serverVersion=15"

# run test suite with postgreSQL
$ vendor/bin/phpunit
```

The `.env.local` file can also be used to override the port of the database containers,
if it does not meet your local requirements. You'll also need to override docker compose configuration:

Here is an example to use MySQL on port `3308`:

```yaml
# docker-compose.override.yml
version: '3.9'

services:
    mysql:
        ports:
            - "3308:3306"
```

```dotenv
# .env.local
DATABASE_URL="mysql://root:1234@127.0.0.1:3308/foundry_test?serverVersion=5.7.42"
```

## Credit

The [AAA](https://www.thephilocoder.com/unit-testing-aaa-pattern/) style of testing was first introduced to me by
[Adam Wathan's](https://adamwathan.me/) excellent [Test Driven Laravel Course](https://course.testdrivenlaravel.com/).
The inspiration for this libraries API comes from [Laravel factories](https://laravel.com/docs/master/database-testing)
and [christophrumpel/laravel-factories-reloaded](https://github.com/christophrumpel/laravel-factories-reloaded).
