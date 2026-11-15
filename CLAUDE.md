# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Git workflow

**Never commit and never open pull requests on your own.** When explicitly asked, push the source branch to a fork, then open the PR against upstream `zenstruck/foundry`.

PR descriptions must be succinct and explain the **why**, not the how.

## Commands

```bash
docker compose up --detach   # DBs required by the test suite (mysql:3307, pgsql:5433, mongo:27018)
composer update              # install deps

# provision static analysis tools (bin/tools) — not run automatically on install
# (tally is required too: phpstan.neon bootstraps bin/tools/tally/vendor/autoload.php)
composer bin phpstan install && composer bin tally install

./phpunit --exclude-group=maker      # main testsuite (wrapper script, NOT vendor/bin/phpunit directly);
                                     # always exclude slow "maker" tests unless working on the maker commands
./phpunit --testsuite reset-database # only when touching the reset-database strategies
./phpunit --filter SomeTest          # single test/class

# static analysis
bin/tools/phpstan/vendor/phpstan/phpstan/phpstan analyse

# benchmarks (Linux only), see "Benchmarks" below
(set -a; source .env; [ -f .env.local ] && source .env.local; vendor/bin/tally run --jobs=1)

# docs linter
docker run --rm -e DOCS_DIR='/docs' -v ${PWD}/docs:/docs oskarstark/doctor-rst --short
```

**Never run php-cs-fixer manually**: code style is auto-fixed by CI.

The suite runs on PHPUnit 13.1; CI also runs it on PHPUnit 11 and 12 (`composer require --dev --no-update "phpunit/phpunit:^11"`).
PHPUnit 11 silently ignores `#[RequiresEnvironmentVariable]` and `#[WithEnvironmentVariable]`: run it with every database (MySQL or PostgreSQL, Mongo, DAMA), and give a test that needs a database disabled a `#[RequiresPhpunit]`.

Always use the `./phpunit` wrapper: it sources `.env`/`.env.local` and wires the right `--extension` flags (Foundry extension, dama/doctrine-test-bundle).

### Test permutations

Driven by env vars (see `.env` for all options). ALWAYS override them in `.env.local`, never edit `.env`, never pass them inline (`VAR=value ./phpunit`). Default fast setup, unless testing something specific:

```bash
# .env.local
DATABASE_URL="sqlite:///%kernel.project_dir%/var/data.db" # sqlite: no docker needed
USE_DAMA_DOCTRINE_TEST_BUNDLE="1"                         # wraps tests in transactions, much faster
MONGO_URL=""                                              # disable Mongo
USE_PHP_84_LAZY_OBJECTS="1"                               # native lazy objects instead of generated proxies
```

The reset-database suite also honors `DATABASE_RESET_MODE` (`schema`|`migrate`|`auto`, the latter migrating only if doctrine-migrations-bundle is installed) and `MIGRATION_CONFIGURATION_FILE` (see README).

### Behat sub-package (src/Test/Behat/)

```bash
composer update --working-dir=src/Test/Behat

src/Test/Behat/vendor/bin/phpunit -c src/Test/Behat/phpunit.dist.xml            # unit tests
src/Test/Behat/vendor/bin/behat -c src/Test/Behat/behat.php                     # functional tests
src/Test/Behat/vendor/bin/behat -c src/Test/Behat/behat.php --profile=<name>    # profiles listed in behat.php
# `main-native-dama` additionally needs `--tags='~@skip-with-native-dama'`; the reference
# invocations for every profile are the `vendor/bin/behat` lines of .github/workflows/behat.yml
bin/tools/phpstan/vendor/phpstan/phpstan/phpstan analyse -c src/Test/Behat/phpstan.neon
```

## Architecture

Foundry is a model-factory library for Symfony/Doctrine (ORM + MongoDB ODM). PHP >= 8.4, Symfony >= 8.0, PHPStan level 9.

**Factory hierarchy** — `Factory` (abstract base, immutable builder: every method returns a clone) → `ObjectFactory` (instantiates unpersisted objects, `beforeInstantiate`/`afterInstantiate` hooks with priorities, object reuse via `reuse()`) → `Persistence\PersistentObjectFactory` (persists via Doctrine, repositories, `afterPersist` hook). `ArrayFactory` and `AnonymousFactoryGenerator` cover ad-hoc cases. `FactoryCollection` handles batches (`many()`, `range()`, `sequence()`, `distribute()`).

**Global state** — `Configuration` is a static, per-kernel-boot singleton holding the `FactoryRegistry`, `StoryRegistry`, faker instance and persistence availability. Tests enable it via the PHPUnit extension `PHPUnit\FoundryExtension`, which is mandatory.

**Persistence layer** (`src/Persistence/`) — `PersistenceManager` + per-Doctrine-flavor `PersistenceStrategy` implementations in `src/ORM/` (ORM 3 only) and `src/Mongo/`. `Relationship/` keeps bidirectional relations consistent. `ResetDatabase/` contains the before-first-test database resetters (schema recreate vs. doctrine-migrations, chosen by `DATABASE_RESET_MODE`). `src/InMemory/` provides the no-database in-memory repositories.

**Entry points** — the API is largely function-based: `src/functions.php`, `src/Persistence/functions.php` (registered as composer `files` autoload). `src/Maker/` provides `make:factory`/`make:story` (templates in `skeleton/`, snapshot tests against `tests/Fixture/Maker/expected/`), `src/Command/` the `foundry:*` console commands. Bundle wiring lives in `ZenstruckFoundryBundle` + `src/DependencyInjection/` + `config/*.php`.

**Behat subpackage** — `src/Test/Behat/` is a standalone package (own composer.json, vendor, testsuite) subtree-split to `zenstruck/foundry-behat`. It is excluded from the main autoload/classmap and from PHPStan; its `symlink-vendor.sh` symlinks the parent `src/` into its vendor dir, so changes to the main package affect it. It is experimental (no BC promise).

**Upgrade tooling** — `utils/psalm/` is a Psalm plugin, autoloaded from the main package. The Rector rules for user-facing migrations live on `2.x` only: they key on classes 3.0 removed (`PersistentProxyObjectFactory`), so they can neither run nor be tested here; `UPGRADE-3.0.md` tells users to run them before upgrading.

**Tests layout** — `tests/Unit` (no kernel), `tests/Integration` (kernel + DB), `tests/WebTestCase` (runs first in the suite on purpose), `tests/Fixture` (entities/documents/factories shared by everything, including Behat and maker tests). `tests/Benchmark` is driven by Tally (`tally.php`).

### Benchmarks

`tests/Benchmark` runs under [phpstan/tally](https://github.com/phpstan/tally), which counts instructions, cycles and memory under Valgrind instead of measuring time.
No expected numbers are committed: the CI runs the PR's benchmarks on `3.x`, accepts those numbers as expected, then runs them on the PR (see `.github/workflows/benchmarks.yml`).
So a benchmark must run against the `3.x` fixtures too, and the benchmark classes must not carry `#[Expected]`/`#[ExpectedFor]` attributes.
Always pass `--jobs=1`: the cases share one database.
Tally passes only the env vars listed in `tally.php` to the cases, and reads neither `.env` nor `.env.local`, hence the subshell above.

Database round trips, which Tally does not see (only the PHP process is counted), are asserted by `tests/Integration/{ORM,Mongo}/RoundTripsTest.php`.

## Code style

- Code (and comments) always in English; specs/plans may be in French.
- Mark classes not meant for end users with `@internal`.
- Keep comments to a strict minimum: only when genuinely useful.
- No phpdoc that paraphrases the type declarations; only useful `@param`/`@return`/`@var`/... annotations (generics, array shapes, information the types cannot express).
- Prefer string interpolation over `sprintf()` when no static call is involved.
- Minimize useless temporary variables.
- Prefer `array_map()`/`array_filter()` and PHP 8.4+ `array_find()`/`array_find_key()`/`array_any()`/`array_all()` over `foreach`, unless it gets too complex.
- Make callables `static` whenever possible.

## Interaction

When asking the user structured questions, always include an extra free-form option for directives possibly unrelated to the current questions.
