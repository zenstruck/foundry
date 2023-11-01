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
 * @extends \Zenstruck\Foundry\ObjectFactory<DummyObject>
 */
final class DummyObjectModelFactory extends \Zenstruck\Foundry\ObjectFactory
{
    protected function defaults(): array
    {
        return [];
    }

    protected function initialize(): static
    {
        return $this;
    }

    public static function class(): string
    {
        return DummyObject::class;
    }
}

?>
