<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Test\Behat\Tests\Unit\Test\Behat;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Zenstruck\Foundry\ObjectFactory;
use Zenstruck\Foundry\Persistence\PersistenceManager;
use Zenstruck\Foundry\Test\Behat\Attribute\FactoryShortName;
use Zenstruck\Foundry\Test\Behat\FactoryShortNameResolver;
use Zenstruck\Foundry\Test\Behat\FoundryTableNode;
use Zenstruck\Foundry\Test\Behat\ObjectRegistry;

final class FoundryTableNodeTest extends TestCase
{
    private FactoryShortNameResolver $factoryResolver;
    private ObjectRegistry $objectRegistry;

    protected function setUp(): void
    {
        $this->factoryResolver = new FactoryShortNameResolver([new TableTestEntityFactory()]);

        $persistenceManager = $this->createStub(PersistenceManager::class);
        $persistenceManager->method('getIdentifierValues')->willReturnCallback(
            static fn(object $object): array => ['id' => $object->id ?? 0]
        );

        $this->objectRegistry = new ObjectRegistry($this->factoryResolver, $persistenceManager);
        $this->objectRegistry->reset();
    }

    #[Test]
    public function it_creates_table_node_with_factory_method(): void
    {
        $table = FoundryTableNode::create(
            $this->factoryResolver,
            $this->objectRegistry,
            [0 => 10, 1 => 10],
            [
                0 => ['name', 'value'],
                1 => ['foo', 'bar'],
            ]
        );

        self::assertSame([['name' => 'foo', 'value' => 'bar']], $table->getColumnsHash());
    }

    #[Test]
    #[DataProvider('valueFormattingProvider')]
    public function it_formats_scalar_values_as_string(mixed $value, string $expected): void
    {
        $table = FoundryTableNode::create(
            $this->factoryResolver,
            $this->objectRegistry,
            [0 => 20],
            [
                0 => ['col'],
                1 => [$value],
            ]
        );

        $row = $table->getRowAsString(1);

        self::assertStringContainsString($expected, $row);
    }

    public static function valueFormattingProvider(): iterable
    {
        yield 'string value' => ['hello', 'hello'];
        yield 'integer value' => [42, '42'];
        yield 'float value' => [3.14, '3.14'];
        yield 'boolean true' => [true, '1'];
        yield 'boolean false' => [false, ''];
        yield 'null value' => [null, ''];
    }

    #[Test]
    public function it_formats_datetime_value(): void
    {
        $date = new \DateTimeImmutable('2024-06-15 14:30:45');

        $table = FoundryTableNode::create(
            $this->factoryResolver,
            $this->objectRegistry,
            [0 => 30],
            [
                0 => ['date'],
                1 => [$date],
            ]
        );

        $row = $table->getRowAsString(1);

        self::assertStringContainsString('2024-06-15 14:30:45', $row);
    }

    #[Test]
    public function it_formats_backed_enum_value(): void
    {
        $table = FoundryTableNode::create(
            $this->factoryResolver,
            $this->objectRegistry,
            [0 => 20],
            [
                0 => ['status'],
                1 => [TableTestStatus::ACTIVE],
            ]
        );

        $row = $table->getRowAsString(1);

        self::assertStringContainsString('active', $row);
    }

    #[Test]
    public function it_formats_int_backed_enum_value(): void
    {
        $table = FoundryTableNode::create(
            $this->factoryResolver,
            $this->objectRegistry,
            [0 => 20],
            [
                0 => ['priority'],
                1 => [TableTestPriority::HIGH],
            ]
        );

        $row = $table->getRowAsString(1);

        self::assertStringContainsString('3', $row);
    }

    #[Test]
    public function it_formats_stored_object_with_factory_name_and_registry_name(): void
    {
        $entity = new TableTestEntity(id: 1, name: 'Test');
        $this->objectRegistry->store($entity, 'my-entity');

        $table = FoundryTableNode::create(
            $this->factoryResolver,
            $this->objectRegistry,
            [0 => 30],
            [
                0 => ['entity'],
                1 => [$entity],
            ]
        );

        $row = $table->getRowAsString(1);

        // Should contain the short name and the registered name
        self::assertStringContainsString('table test entity', $row);
        self::assertStringContainsString('my-entity', $row);
    }

    #[Test]
    public function it_throws_for_unsupported_object_type(): void
    {
        $unsupportedObject = new \stdClass();

        $table = FoundryTableNode::create(
            $this->factoryResolver,
            $this->objectRegistry,
            [0 => 20],
            [
                0 => ['obj'],
                1 => [$unsupportedObject],
            ]
        );

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Unsupported value type: stdClass');

        $table->getRowAsString(1);
    }

    #[Test]
    public function it_formats_row_with_wrapped_values(): void
    {
        $table = FoundryTableNode::create(
            $this->factoryResolver,
            $this->objectRegistry,
            [0 => 10, 1 => 10],
            [
                0 => ['a', 'b'],
                1 => ['foo', 'bar'],
            ]
        );

        $row = $table->getRowAsStringWithWrappedValues(1, static fn(string $value, int $column) => "[{$column}:{$value}]");

        self::assertStringContainsString('[0:', $row);
        self::assertStringContainsString('[1:', $row);
    }
}

class TableTestEntity
{
    public function __construct(
        public int $id,
        public string $name,
    ) {
    }
}

enum TableTestStatus: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
}

enum TableTestPriority: int
{
    case LOW = 1;
    case MEDIUM = 2;
    case HIGH = 3;
}

/** @extends ObjectFactory<TableTestEntity> */
#[FactoryShortName('table test entity')]
final class TableTestEntityFactory extends ObjectFactory
{
    public static function class(): string
    {
        return TableTestEntity::class;
    }

    protected function defaults(): array
    {
        return ['id' => 1, 'name' => 'Test'];
    }
}
