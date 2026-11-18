---
name: make-story
description: Interactively create a Zenstruck Foundry story (fixture-building class), or add build logic to an existing one
---

# Make Story

Generate a Zenstruck Foundry `Story` class — a reusable set of fixtures assembled by calling
factories — or add build logic to an existing story.

## Arguments

- `<StoryName>`: The PascalCase story name (e.g., `make-story DefaultCategories`). The `Story`
  suffix is optional — it is appended automatically if missing.
- No args: prompt the user for the story name first.

## Workflow

### Step 0 — Detect project conventions

Before anything else, read `composer.json` to find the PSR-4 autoload roots:
- The `autoload.psr-4` root namespace and source path (e.g. `"App\\"` → `"src/"`).
- The `autoload-dev.psr-4` test root (e.g. `"App\\Tests\\"` → `"tests/"`).

If no PSR-4 entry exists, default to namespace `App` / source path `src/`, and test namespace
`App\Tests` / path `tests/`.

Foundry's default story namespace is `Story` (configurable via
`make_story.default_namespace` in `config/packages/zenstruck_foundry.yaml`). Check that file for
an override before falling back to `Story`.

Derived paths:
- **App story** (default): directory `<src>/Story/`, namespace `<RootNamespace>\Story`
- **Test story** (`--test`): directory `<testDir>/Story/`, namespace `<RootNamespace>\Tests\Story`

If the user passes a `--namespace <NS>` value, use it in place of `Story` (still relative to the
root namespace, and still prefixed with `Tests\` when `--test` is given).

### Step 1 — Gather story name

If no argument was given, ask the user for the story name. Validate it is PascalCase with no
namespace prefix (just the class name, e.g. `DefaultCategories` or `DefaultCategoriesStory`).

Normalise the name so it ends with `Story` — append the suffix if the user did not include it
(`DefaultCategories` → `DefaultCategoriesStory`).

### Step 2 — Choose location

Ask whether this is:
- An **application story** (default) → `<src>/Story/` — used for real fixtures / dev data.
- A **test story** (`--test`) → `<testDir>/Story/` — used only in the test suite.

Resolve the target directory and namespace from Step 0 accordingly.

After resolving the name and location, check whether `<storyDir>/<StoryName>.php` exists:

- **File does not exist** → proceed to Step 3 (create mode).
- **File exists** → switch to **add-build mode**: read the existing story, show the user the
  current `build()` body, then proceed to Step 3 to collect only the *new* fixture logic to
  append. Skip repository-style boilerplate — you are only editing the `build()` method (and
  imports).

### Step 3 — Interview for the story build

A story's job is to assemble fixtures by calling factories inside `build()`. Interactively gather
what the story should create. Ask about:

- **Which factories to call** and with what attributes (e.g. `CategoryFactory::createOne(['name' => 'PHP'])`,
  `UserFactory::createMany(10)`). Ask for the factory class name and how many objects.
- **Named references** — objects other code should be able to fetch later. These use
  `$this->addState('reference-name', <object>)` and are retrieved with
  `<StoryName>::get('reference-name')`.
- **Pools** — groups of objects for random selection. Added with
  `$this->addToPool('pool-name', <object>)` (or the `$pool` argument of `addState`) and retrieved
  with `<StoryName>::getRandom('pool-name')`, `getRandomSet('pool-name', n)`,
  `getRandomRange('pool-name', min, max)`, or `getPool('pool-name')`.

Keep asking "Add another factory call / reference / pool?" until the user says no. If the user is
unsure, generate a minimal stub `build()` with a `// TODO` comment and let them fill it in later.

### Step 3b — Additional customisations

After collecting the build logic, ask the user once:

> "Anything else? (e.g. register this story as a named fixture, or reference other stories.)"

Offer these as preset options plus an "Other / none" escape:
- `AsFixture` — add `#[AsFixture(name: '...')]` (optionally `groups: [...]`) so the story is
  loadable by name via the fixtures system. Ask for the fixture name (and any groups). This is
  the convention used elsewhere in this project.
- `Depend on another story` — call `OtherStory::load()` at the top of `build()` so its fixtures
  exist first, then reference them via `OtherStory::get(...)`.
- `None / done` — no extras.

Allow multiple selections. If the user picks "Other", read their free-text note and incorporate it.

### Step 4 — Present plan

Before writing files, print a summary and ask the user to confirm.

**Create mode:**
- Story class: `<storyDir>/<StoryName>.php` (new)
- Namespace: `<resolved namespace>`
- Factory calls listed (factory | count | attributes)
- Named references listed
- Pools listed
- Extras listed (`AsFixture`, story dependencies, etc.)

**Add-build mode:**
- Story class: `<storyDir>/<StoryName>.php` (will be edited)
- New factory calls / references / pools listed
- Extras listed

### Step 5 — Generate / Update the Story

**Create mode** — write `<storyDir>/<StoryName>.php`.

**Base skeleton:**

```php
<?php

namespace <Namespace>;

use Zenstruck\Foundry\Story;

final class <StoryName> extends Story
{
    public function build(): void
    {
        // ... fixture logic
    }
}
```

**Rules for the generated class:**
- Always `final`.
- Extends `Zenstruck\Foundry\Story`.
- Implements the abstract `build(): void` method.
- Import each factory class actually referenced in `build()` (from its own namespace, commonly
  `<RootNamespace>\Factory\<X>Factory`). Only import what is used.
- Use `$this->addState('name', <object>)` for named references and `$this->addToPool('pool', <object>)`
  (or `addState('name', <object>, 'pool')`) for pools.
- If the `AsFixture` extra was chosen, add `use Zenstruck\Foundry\Attribute\AsFixture;` and place
  `#[AsFixture(name: '<name>')]` (with `groups: [...]` if given) directly above the class.
- If depending on another story, call `<OtherStory>::load();` first inside `build()` and import it.
- If the user gave no build logic, emit a single `// TODO build your story here` comment inside
  `build()`.
- Always end the file with a newline.

**Add-build mode** — edit the existing `<storyDir>/<StoryName>.php`:
1. Read the current file.
2. Insert the new statements inside the existing `build()` method, after the current body (remove a
   lone `// TODO ...` placeholder if present).
3. Add any missing `use` statements (factory classes, `AsFixture`, other stories) to the import block.
4. If `AsFixture` was chosen and no attribute exists yet, add it above the class declaration.
5. Make targeted edits — do not rewrite the whole file from scratch.

**Example generated story:**

```php
<?php

namespace App\Story;

use App\Factory\CategoryFactory;
use Zenstruck\Foundry\Attribute\AsFixture;
use Zenstruck\Foundry\Story;

#[AsFixture(name: 'default-categories')]
final class DefaultCategoriesStory extends Story
{
    public function build(): void
    {
        $this->addState('php', CategoryFactory::createOne(['name' => 'PHP']));

        CategoryFactory::createMany(5); // adds 5 random categories

        foreach (['Books', 'Movies', 'Music'] as $name) {
            $this->addToPool('media', CategoryFactory::createOne(['name' => $name]));
        }
    }
}
```

### Step 6 — Post-generation instructions

After writing the file, tell the user:

1. Review the story and adjust factory attributes / counts.
2. Make sure the referenced factories exist — generate any missing ones with the `make-factory`
   skill.
3. Load / use the story:
   - In tests: add the `#[WithStory(<StoryName>::class)]` attribute (`use Zenstruck\Foundry\Attribute\WithStory;`)
     to the test class/method, or call `<StoryName>::load()` directly.
   - As a named fixture (if `#[AsFixture]` was added): load it via the fixtures system /
     `foundry:load-story` and fetch objects with `<StoryName>::get('...')`,
     `<StoryName>::getRandom('...')`, etc.

## Rules

- If `<storyDir>/<StoryName>.php` already exists, switch to add-build mode — never overwrite the whole file.
- In add-build mode, make surgical inserts into `build()`; never rewrite the file from scratch.
- Always append the `Story` suffix to the class name if the user omitted it.
- The class is always `final` and extends `Zenstruck\Foundry\Story`.
- Do not import unused classes.
- Do not add comments unless something is non-obvious (or the user asked for a TODO stub).
- Named references use `addState` / `get`; random pools use `addToPool` / `getRandom` &
  `getRandomSet` / `getRandomRange`.
