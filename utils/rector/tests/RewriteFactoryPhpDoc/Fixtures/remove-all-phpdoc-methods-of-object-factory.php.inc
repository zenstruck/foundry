<?php

namespace Zenstruck\Foundry\Utils\Rector\Tests\Fixtures;

use Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyObject;
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;

/**
 * @extends ModelFactory<DummyObject>
 *
 * @method        DummyObject|Proxy     create(array|callable $attributes = [])
 * @method static DummyObject|Proxy     createOne(array $attributes = [])
 * @method static DummyObject[]|Proxy[] createMany(int $number, array|callable $attributes = [])
 * @method static DummyObject[]|Proxy[] createSequence(iterable|callable $sequence)
 *
 * @method customMethod()
 */
final class DummyObjectModelFactory extends ModelFactory
{
    protected function getDefaults(): array
    {
        return [];
    }

    protected function initialize(): self
    {
        return $this;
    }

    protected static function getClass(): string
    {
        return DummyObject::class;
    }
}
?>
-----
<?php

namespace Zenstruck\Foundry\Utils\Rector\Tests\Fixtures;

use Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyObject;
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;

/**
 * @extends ModelFactory<DummyObject>
 *
 *
 * @method customMethod()
 */
final class DummyObjectModelFactory extends ModelFactory
{
    protected function getDefaults(): array
    {
        return [];
    }

    protected function initialize(): self
    {
        return $this;
    }

    protected static function getClass(): string
    {
        return DummyObject::class;
    }
}
?>
