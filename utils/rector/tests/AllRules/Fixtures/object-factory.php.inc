<?php

namespace Zenstruck\Foundry\Utils\Rector\Tests\Fixtures;

use Zenstruck\Foundry\Object\Instantiator;
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

    public function published(): static
    {
        return $this->addState(['published' => true]);
    }

    protected function initialize(): self
    {
        return $this
            ->withoutPersisting()
            ->instantiateWith(
            static fn() => (new Instantiator())
                ->allowExtraAttributes(['some', 'fields'])
                ->alwaysForceProperties(['other', 'fields'])
                ->allowExtraAttributes()
                ->alwaysForceProperties()
        );
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

use Zenstruck\Foundry\ObjectFactory;
use Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyObject;

/**
 * @extends ObjectFactory<DummyObject>
 */
final class DummyObjectModelFactory extends \Zenstruck\Foundry\ObjectFactory
{
    protected function defaults(): array
    {
        return [];
    }

    public function published(): static
    {
        return $this->with(['published' => true]);
    }

    protected function initialize(): static
    {
        return $this
            ->instantiateWith(
            static fn() => (\Zenstruck\Foundry\Object\Instantiator::withoutConstructor())
                ->allowExtra(...['some', 'fields'])
                ->alwaysForce(...['other', 'fields'])
                ->allowExtra()
                ->alwaysForce()
        );
    }

    public static function class(): string
    {
        return DummyObject::class;
    }
}

?>
