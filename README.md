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

## How to contribute

The test suite of this library needs one or more database, and static analysis needs to be ran on the smaller PHP version
supported (currently PHP 7.2), then it comes with a full docker stack.

### Install docker

You must [install docker](https://docs.docker.com/engine/install/) and [install docker-compose](https://docs.docker.com/compose/install/)
at first before running the tests.

### Run tests

The library is shipped with a `Makefile` to run tests.
Each target will build and start the docker stack and install composer only if needed.

```shell
$ make help
validate                       Run fixcs, sca, full test suite and validate migrations
test-full                      Run full PHPunit (MySQL + Mongo)
test-fast                      Run PHPunit with SQLite
test-mysql                     Run PHPunit with mysql
test-postgresql                Run PHPunit with postgreSQL
test-mongo                     Run PHPunit with Mongo
fixcs                          Run PHP CS-Fixer
sca                            Run Psalm
database-generate-migration    Generate new migration based on mapping in Zenstruck\Foundry\Tests\Fixtures\Entity
database-validate-mapping      Validate mapping in Zenstruck\Foundry\Tests\Fixtures\Entity
database-drop-schema           Drop database schema
docker-start                   Build and run containers
docker-stop                    Stop containers
docker-purge                   Purge containers
composer                       Run composer command
clear                          Start from a fresh install (needed if vendors have already been installed with another php version)
```

You can run each `test-*` target with a special argument `filter`:
```shell
$ make test-mysql filter=FactoryTest
```

which will use PHPUnit's `--filter` option.

### Change docker's ports

You can create a `.env` file to change the ports used by docker:
```dotenv
MYSQL_PORT=3307
POSTGRES_PORT=5434
MONGO_PORT=27018
```

### Execute commands in php container

You can execute any command into the php container using docker compose:
```shell
$ docker-compose exec php [you commmand] # or "docker compose" depending on your docker compose version
```

### Using xdebug with PhpStorm

The php container is shipped with xdebug activated. You can use step by step debugging session with PhpStorm: you should
create a server called `FOUNDRY` in your PHP Remote Debug, with the IDE key `xdebug_foundry`

![PhpStorm with xdebug](docs/phpstorm-xdebug-config.png)

### Run tests without docker

If for any reason docker is not available on your computer, the target `make test-fast` will run tests with your local
php version, and sqlite will be used as database. Results may differ from the CI!

## Migrations

Whenever an entity in the fixtures is added or updated a migration must be generated with `make migrations-generate`

## Credit

The [AAA](https://www.thephilocoder.com/unit-testing-aaa-pattern/) style of testing was first introduced to me by
[Adam Wathan's](https://adamwathan.me/) excellent [Test Driven Laravel Course](https://course.testdrivenlaravel.com/).
The inspiration for this libraries API comes from [Laravel factories](https://laravel.com/docs/master/database-testing)
and [christophrumpel/laravel-factories-reloaded](https://github.com/christophrumpel/laravel-factories-reloaded).
