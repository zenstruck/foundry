<?php

use Zenstruck\Foundry\Factory;
use Zenstruck\Foundry\Test\TestState;

use function Zenstruck\Foundry\create;
use function Zenstruck\Foundry\instantiate;
use function Zenstruck\Foundry\repository;

create(SomeClass::class, []);
instantiate(SomeClass::class, ['published' => true]);
repository($someObject);

Factory::delayFlush(static fn() => true);
TestState::configure(faker: null);

?>
-----
<?php

\Zenstruck\Foundry\Persistence\persist(SomeClass::class, []);
\Zenstruck\Foundry\object(SomeClass::class, ['published' => true]);
\Zenstruck\Foundry\Persistence\repository($someObject);

\Zenstruck\Foundry\Persistence\flush_after(static fn() => true);
\Zenstruck\Foundry\Test\UnitTestConfig::configure(faker: null);

?>
