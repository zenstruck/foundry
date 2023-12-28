<?php

\Zenstruck\Foundry\create(SomeClass::class, []);
\Zenstruck\Foundry\instantiate(SomeClass::class, ['published' => true]);
\Zenstruck\Foundry\repository($someObject);

\Zenstruck\Foundry\Factory::delayFlush(static fn() => true);
\Zenstruck\Foundry\Test\TestState::configure(faker: null);

?>
-----
<?php

\Zenstruck\Foundry\Persistence\persist(SomeClass::class, []);
\Zenstruck\Foundry\object(SomeClass::class, ['published' => true]);
\Zenstruck\Foundry\Persistence\repository($someObject);

\Zenstruck\Foundry\Persistence\flush_after(static fn() => true);
\Zenstruck\Foundry\Test\UnitTestConfig::configure(faker: null);

?>
