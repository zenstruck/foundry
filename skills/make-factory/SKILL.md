---
name: make-factory
description: Create a Zenstruck Foundry model factory for a Doctrine entity (or a plain object), with default values guessed from the entity's mapping
---

# Make Factory

Generate a Zenstruck Foundry factory class for a Doctrine entity/document or a plain object. It
picks the right base factory class, guesses `defaults()` from the entity's Doctrine mapping, and
adds the standard hint scaffolding.

## Arguments

- `<Class>`: The entity/class to create a factory for — a short name (`Post`) or a fully-qualified
  class name (`App\Entity\Post`). The `Factory` suffix is appended to the generated class name
  automatically.
- No args: prompt the user (list entities under `<src>/Entity/`, or accept an FQCN for a
  non-persisted object).

## Workflow

### Step 0 — Detect project conventions

Read `composer.json` for the PSR-4 roots:
- `autoload.psr-4` root + source path (e.g. `"App\\"` → `"src/"`).
- `autoload-dev.psr-4` test root (e.g. `"App\\Tests\\"` → `"tests/"`).

Defaults if absent: namespace `App` / `src/`, test namespace `App\Tests` / `tests/`.

Foundry's default factory namespace is `Factory` (override: `make_factory.default_namespace` in
`config/packages/zenstruck_foundry.yaml`). Also honour `make_factory.add_hints` (default **true**).

Determine the **PHP version** from `composer.json` `require.php` — it selects the base class
(below). This project requires `>=8.4`.

Derived paths:
- **App factory** (default): `<src>/Factory/`, namespace `<RootNamespace>\Factory`
- **Test factory** (`--test`): `<testDir>/Factory/`, namespace `<RootNamespace>\Tests\Factory`

If the user passes `--namespace <NS>`, use it in place of `Factory` (still relative to the root
namespace, still prefixed with `Tests\` when `--test`). For an entity in a sub-namespace
(`App\Entity\Blog\Post`), mirror the suffix under the factory namespace (`App\Factory\Blog`).

### Step 1 — Resolve the target class

- If a short name was given, resolve it to `<src>/Entity/<Name>.php`. If that file doesn't exist,
  ask the user for the full class (it may be a non-Doctrine object → treat as `--no-persistence`).
- If no argument, list the entity classes under `<src>/Entity/` and let the user choose, or let
  them type an FQCN for a plain object.
- The generated factory class name is `<ShortName>Factory` (e.g. `Post` → `PostFactory`).

### Step 2 — Persistence & options

Decide whether the target is **persisted** (a Doctrine entity — its class has an `#[ORM\Entity]`
attribute / is a managed class) or **not persisted** (`--no-persistence`). This selects the base
class:

| Case | Base class (FQCN) |
|------|-------------------|
| Persisted, PHP ≥ 8.4 | `Zenstruck\Foundry\Persistence\PersistentObjectFactory` |
| Persisted, PHP < 8.4 | `Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory` |
| Not persisted (`--no-persistence`) | `Zenstruck\Foundry\ObjectFactory` |

Ask the user (offer sensible defaults) about these options:
- **`--test`** — generate under `tests/` instead of `src/`.
- **`--all-fields`** — generate defaults for *all* columns, not only required (non-nullable) ones.
  Default: only required fields.
- **Hints** — include the beginner hint scaffolding (constructor, `initialize()`, todo phpdocs).
  Defaults to the `add_hints` config value (true).
- **`--with-phpdoc`** — add `@method`/`@phpstan-method` phpdoc. **Discouraged** and off by default;
  only include it if the user explicitly asks.

Resolve the factory path `<factoryDir>/<ShortName>Factory.php`. If it already exists, show the user
and ask whether to overwrite before continuing — do not silently clobber.

### Step 3 — Guess `defaults()` from the mapping

For a **persisted** entity, read the entity file and build the `defaults()` array by walking its
mapped fields **in declaration order**:

- **Skip** the identifier (`id`) and any field that is nullable — *unless* `--all-fields` is set.
- Map each Doctrine column `type` to a faker default (length `N` substituted from `length:`):

  | Doctrine type | Generated value |
  |---------------|-----------------|
  | `string`, `text`, `ascii_string` | `self::faker()->text(N),` (omit `N` if no length) |
  | `boolean` | `self::faker()->boolean(),` |
  | `integer`, `int`, `bigint` | `self::faker()->randomNumber(),` |
  | `smallint` | `self::faker()->numberBetween(1, 32767),` |
  | `float`, `decimal` | `self::faker()->randomFloat(),` |
  | `datetime`, `datetime_mutable`, `date`, `time` | `self::faker()->dateTime(),` |
  | `datetime_immutable`, `date_immutable` | `\DateTimeImmutable::createFromMutable(self::faker()->dateTime()),` |
  | `json`, `array`, `simple_array` | `[],` |
  | `guid` | `self::faker()->uuid(),` |
  | `uuid` | `Uuid::fromString(self::faker()->uuid()),` (add `use Symfony\Component\Uid\Uuid;`) |
  | *unknown* | `null, // TODO add <TYPE> type manually` |

- **Enum columns** (`enumType:`): generate a random case, e.g.
  `self::faker()->randomElement(<Enum>::cases()),` and import the enum.
- **Required (non-nullable) to-one relations** (`ManyToOne`/`OneToOne` owning, join column not
  nullable): add `'<field>' => <TargetShortName>Factory::new(),` and note the related factory must
  exist. Skip to-many relations. Nullable relations are only included with `--all-fields`.

For a **non-persisted** object, generate an empty `defaults()` (return `[]`) with a todo hint — a
plain class has no mapping to introspect.

Emit the array keys in field declaration order.

### Step 4 — Present plan

Print a summary and ask the user to confirm:
- Factory class: `<factoryDir>/<ShortName>Factory.php` (new, or **overwrite** if it exists)
- Target class + base factory class
- Options: test / all-fields / hints / with-phpdoc
- The computed `defaults()` entries (field → value)
- Any imports that will be added (entity, `Uuid`, related factories, enums)
- Warn about referenced factories/entities that don't exist yet

### Step 5 — Generate the factory

Write `<factoryDir>/<ShortName>Factory.php` in this shape:

```php
<?php

namespace <FactoryNamespace>;

use <Target FQCN>;
use <Base factory FQCN>;
<...any extra uses: Uuid, related factories, enums...>

/**
 * @extends <BaseFactoryShortName><<TargetShortName>>
 */
final class <ShortName>Factory extends <BaseFactoryShortName>
{
    #[\Override]
    public static function class(): string
    {
        return <TargetShortName>::class;
    }

    #[\Override]
    protected function defaults(): array|callable
    {
        return [
            // guessed defaults, one per line: '<field>' => <value>,
        ];
    }

    #[\Override]
    protected function initialize(): static
    {
        return $this
            // ->afterInstantiate(function(<TargetShortName> $<var>): void {})
        ;
    }
}
```

Rules for generation:
- Class is always `final` and extends the resolved base factory class.
- Always include the `@extends <Base><Target>` phpdoc and the `class()` method.
- **`#[\Override]`** on `class()`, `defaults()`, and `initialize()` only when PHP ≥ 8.3 (true here).
- **Hints on** (default): also emit the `__construct()` hint block, the `// @todo add your default
  values here` phpdoc, `initialize()`, and the `defaults(): array|callable` return type. **Hints
  off**: omit `__construct()` and `initialize()`, and use `defaults(): array` (no `|callable`), and
  drop the todo phpdocs.
- `--with-phpdoc` (only if requested): add the `@method`/`@phpstan-method` lines above the class
  (e.g. `@method Post create(...)`, static + instance variants).
- Import only what is used; group the entity/object and base class first, extra uses after.
- Always end the file with a trailing newline.

### Step 6 — Post-generation instructions

Tell the user:
1. Open the factory and refine `defaults()` / add named states in `initialize()`.
2. Make sure any **related factories** referenced in `defaults()` exist (generate them with this
   skill).
3. Use it: `<ShortName>Factory::createOne([...])`, `::createMany(n)`,
   `::new()->withoutPersisting()`, etc. Reference it from stories (see the `make-story` skill).
4. Docs: https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#factories

## Rules

- Pick the base class from persistence + PHP version (see the table); never hardcode one.
- Guess `defaults()` from the actual entity mapping — skip `id` and nullable fields unless
  `--all-fields`.
- The factory class name is always `<ShortName>Factory`; the class is always `final`.
- Do not import unused classes; add `Uuid`/enum/related-factory imports only when referenced.
- Respect the `add_hints` config for the hint scaffolding; omit `--with-phpdoc` unless requested
  (it is deprecated).
- If the factory file already exists, confirm an overwrite — there is no partial "add" mode.
