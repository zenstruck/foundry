# UPGRADE

## From 1.35 to 1.36

### Factories

- Extend `Zenstruck\Foundry\Persistence\PersistentObjectFactory` instead of `Zenstruck\Foundry\ModelFactory` for all factories dealing with ORM entities or ODM documents.
- Extend `Zenstruck\Foundry\Object\ObjectFactory` instead of `Zenstruck\Foundry\ModelFactory` for all factories which don't need to persist objects.

### PHPStan

- Install Foundry's extension for PHPStan:
  - will be done automatically if you have `phpstan/extension-installer` installed
  - otherwise, include manually `vendor/zenstruck/foundry/phpstan-foundry.neon` in `phpstan.neon`
- remove all `@phpstan-method` annotations in your factories

### Psalm

- Install Foundry's extension for Psalm:
  - run `vendor/bin/psalm-plugin enable zenstruck/foundry`
  - OR add the following snippet to your `psalm.xml`:
    ```xml
    <plugins>
        <pluginClass class="Zenstruck\Psalm\FoundryPlugin" />
    </plugins>
    ```
- remove all `@psalm-method` annotations in your factories

