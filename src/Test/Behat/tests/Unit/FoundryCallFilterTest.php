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

use Behat\Behat\Definition\Call\DefinitionCall;
use Behat\Behat\Definition\Definition;
use Behat\Gherkin\Node\ExampleTableNode;
use Behat\Gherkin\Node\FeatureNode;
use Behat\Gherkin\Node\StepNode;
use Behat\Gherkin\Node\TableNode;
use Behat\Testwork\Call\Call;
use Behat\Testwork\Environment\Environment;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Zenstruck\Foundry\ObjectFactory;
use Zenstruck\Foundry\Persistence\PersistenceManager;
use Zenstruck\Foundry\Test\Behat\Attribute\FactoryShortName;
use Zenstruck\Foundry\Test\Behat\Exception\InvalidObjectParameter;
use Zenstruck\Foundry\Test\Behat\FactoryShortNameResolver;
use Zenstruck\Foundry\Test\Behat\FoundryCallFilter;
use Zenstruck\Foundry\Test\Behat\FoundryContext;
use Zenstruck\Foundry\Test\Behat\FoundryTableNode;
use Zenstruck\Foundry\Test\Behat\ObjectRegistry;

final class FoundryCallFilterTest extends TestCase
{
    private FoundryCallFilter $filter;
    private FactoryShortNameResolver $factoryResolver;
    private ObjectRegistry $objectRegistry;

    protected function setUp(): void
    {
        $this->factoryResolver = new FactoryShortNameResolver([
            new TestEntityFactory(),
            new DatedEntityFactory(),
            new EnumEntityFactory(),
            new RelationEntityFactory(),
            new ChildEntityFactory(),
        ]);
        $this->objectRegistry = new ObjectRegistry(
            $this->factoryResolver, $this->createStub(PersistenceManager::class)
        );
        $this->objectRegistry->reset();

        $this->filter = $this->createFilterWithMockedKernel();
    }

    #[Test]
    public function it_supports_call_with_table_node_argument(): void
    {
        $call = $this->createStub(Call::class);
        $call->method('getArguments')->willReturn([new TableNode([['foo'], ['bar']])]);

        self::assertTrue($this->filter->supportsCall($call));
    }

    #[Test]
    public function it_does_not_support_call_without_table_node(): void
    {
        $call = $this->createStub(Call::class);
        $call->method('getArguments')->willReturn(['foo', 'bar']);

        self::assertFalse($this->filter->supportsCall($call));
    }

    #[Test]
    public function it_does_not_support_call_with_example_table_node(): void
    {
        $call = $this->createStub(Call::class);
        $call->method('getArguments')->willReturn([new ExampleTableNode([['foo'], ['bar']], 'example')]);

        self::assertFalse($this->filter->supportsCall($call));
    }

    #[Test]
    public function it_returns_call_unchanged_when_not_definition_call(): void
    {
        $call = $this->createStub(Call::class);
        $call->method('getArguments')->willReturn([new TableNode([['foo'], ['bar']])]);

        $result = $this->filter->filterCall($call);

        self::assertSame($call, $result);
    }

    #[Test]
    public function it_throws_when_no_factory_short_name_argument(): void
    {
        $call = $this->createDefinitionCallForFoundryContext(
            ['table' => new TableNode([['property'], ['value']])],
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot filter call without a "$factoryShortName" argument.');

        $this->filter->filterCall($call);
    }

    #[Test]
    #[DataProvider('tableNormalizationProvider')]
    public function it_normalizes_table_values(array $input, array $expected): void
    {
        $table = $this->createTableNodeFromRow($input);
        $call = $this->createDefinitionCallForFoundryContext([
            'factoryShortName' => 'test entity',
            'table' => $table,
        ]);

        $result = $this->filter->filterCall($call);
        self::assertInstanceOf(DefinitionCall::class, $result);

        $normalizedTable = $result->getArguments()['table'];
        self::assertInstanceOf(FoundryTableNode::class, $normalizedTable);

        $rows = $normalizedTable->getColumnsHash();
        self::assertCount(1, $rows);
        self::assertEquals($expected, $rows[0]);
    }

    public static function tableNormalizationProvider(): iterable
    {
        yield 'null value' => [
            ['property' => 'null'],
            ['property' => null],
        ];

        yield 'true value' => [
            ['property' => 'true'],
            ['property' => true],
        ];

        yield 'false value' => [
            ['property' => 'false'],
            ['property' => false],
        ];

        yield 'string value kept as-is' => [
            ['property' => 'some text'],
            ['property' => 'some text'],
        ];

        yield 'numeric string kept as-is for unknown property' => [
            ['property' => '42'],
            ['property' => '42'],
        ];

        yield '_ref column is passed through' => [
            ['_ref' => 'my-ref'],
            ['_ref' => 'my-ref'],
        ];
    }

    #[Test]
    public function it_normalizes_object_reference_with_explicit_factory(): void
    {
        $referencedObject = new TestEntity(id: 1, name: 'Referenced');
        $this->objectRegistry->store($referencedObject, 'the-ref');

        $table = $this->createTableNodeFromRow(['relation' => '<ref(test entity, the-ref)>']);
        $call = $this->createDefinitionCallForFoundryContext([
            'factoryShortName' => 'test entity',
            'table' => $table,
        ]);

        $result = $this->filter->filterCall($call);
        self::assertInstanceOf(DefinitionCall::class, $result);

        $normalizedTable = $result->getArguments()['table'];
        $rows = $normalizedTable->getColumnsHash();

        self::assertSame($referencedObject, $rows[0]['relation']);
    }

    #[Test]
    public function it_throws_on_invalid_object_reference(): void
    {
        $table = $this->createTableNodeFromRow(['relation' => '<ref(test entity, nonexistent)>']);
        $call = $this->createDefinitionCallForFoundryContext([
            'factoryShortName' => 'test entity',
            'table' => $table,
        ]);

        $this->expectException(InvalidObjectParameter::class);
        $this->expectExceptionMessage('A reference to an object cannot be resolved in the table, at column "relation"');

        $this->filter->filterCall($call);
    }

    #[Test]
    public function it_normalizes_date_value(): void
    {
        $table = $this->createTableNodeFromRow(['createdAt' => '2024-01-15 10:30:00']);
        $call = $this->createDefinitionCallForFoundryContext([
            'factoryShortName' => 'dated entity',
            'table' => $table,
        ]);

        $result = $this->filter->filterCall($call);
        self::assertInstanceOf(DefinitionCall::class, $result);

        $normalizedTable = $result->getArguments()['table'];
        $rows = $normalizedTable->getColumnsHash();

        self::assertInstanceOf(\DateTimeImmutable::class, $rows[0]['createdAt']);
        self::assertSame('2024-01-15 10:30:00', $rows[0]['createdAt']->format('Y-m-d H:i:s'));
    }

    #[Test]
    public function it_throws_on_invalid_date_value(): void
    {
        $table = $this->createTableNodeFromRow(['createdAt' => 'not-a-date']);
        $call = $this->createDefinitionCallForFoundryContext([
            'factoryShortName' => 'dated entity',
            'table' => $table,
        ]);

        $this->expectException(InvalidObjectParameter::class);
        $this->expectExceptionMessage('Invalid date given "not-a-date", at column "createdAt"');

        $this->filter->filterCall($call);
    }

    #[Test]
    public function it_normalizes_string_enum_value(): void
    {
        $table = $this->createTableNodeFromRow(['status' => 'active']);
        $call = $this->createDefinitionCallForFoundryContext([
            'factoryShortName' => 'enum entity',
            'table' => $table,
        ]);

        $result = $this->filter->filterCall($call);
        self::assertInstanceOf(DefinitionCall::class, $result);

        $normalizedTable = $result->getArguments()['table'];
        $rows = $normalizedTable->getColumnsHash();

        self::assertSame(TestStatus::ACTIVE, $rows[0]['status']);
    }

    #[Test]
    public function it_normalizes_int_enum_value(): void
    {
        $table = $this->createTableNodeFromRow(['priority' => '2']);
        $call = $this->createDefinitionCallForFoundryContext([
            'factoryShortName' => 'enum entity',
            'table' => $table,
        ]);

        $result = $this->filter->filterCall($call);
        self::assertInstanceOf(DefinitionCall::class, $result);

        $normalizedTable = $result->getArguments()['table'];
        $rows = $normalizedTable->getColumnsHash();

        self::assertSame(TestPriority::MEDIUM, $rows[0]['priority']);
    }

    #[Test]
    public function it_throws_on_invalid_enum_value(): void
    {
        $table = $this->createTableNodeFromRow(['status' => 'invalid_status']);
        $call = $this->createDefinitionCallForFoundryContext([
            'factoryShortName' => 'enum entity',
            'table' => $table,
        ]);

        $this->expectException(InvalidObjectParameter::class);
        $this->expectExceptionMessage('Invalid enum value given "invalid_status", at column "status"');

        $this->filter->filterCall($call);
    }

    #[Test]
    public function it_normalizes_object_by_type_inference(): void
    {
        $referencedEntity = new TestEntity(id: 1, name: 'Referenced');
        $this->objectRegistry->store($referencedEntity, 'the-relation');

        $table = $this->createTableNodeFromRow(['relation' => 'the-relation']);
        $call = $this->createDefinitionCallForFoundryContext([
            'factoryShortName' => 'relation entity',
            'table' => $table,
        ]);

        $result = $this->filter->filterCall($call);
        self::assertInstanceOf(DefinitionCall::class, $result);

        $normalizedTable = $result->getArguments()['table'];
        $rows = $normalizedTable->getColumnsHash();

        self::assertSame($referencedEntity, $rows[0]['relation']);
    }

    #[Test]
    public function it_handles_inherited_property_from_parent_class(): void
    {
        $table = $this->createTableNodeFromRow(['inheritedProperty' => 'value']);
        $call = $this->createDefinitionCallForFoundryContext([
            'factoryShortName' => 'child entity',
            'table' => $table,
        ]);

        $result = $this->filter->filterCall($call);
        self::assertInstanceOf(DefinitionCall::class, $result);

        $normalizedTable = $result->getArguments()['table'];
        $rows = $normalizedTable->getColumnsHash();

        self::assertSame('value', $rows[0]['inheritedProperty']);
    }

    /**
     * @param array<string, mixed> $row
     */
    private function createTableNodeFromRow(array $row): TableNode
    {
        return new TableNode([
            \array_keys($row),
            \array_values($row),
        ]);
    }

    private function createFilterWithMockedKernel(): FoundryCallFilter
    {
        $container = $this->createStub(ContainerInterface::class);
        $container->method('get')->willReturnCallback(fn(string $id) => match ($id) {
            '.zenstruck_foundry.behat.factory_resolver' => $this->factoryResolver,
            '.zenstruck_foundry.behat.object_registry' => $this->objectRegistry,
            default => throw new \InvalidArgumentException("Unknown service: {$id}"),
        });

        $kernel = $this->createStub(KernelInterface::class);
        $kernel->method('getContainer')->willReturn($container);

        return new FoundryCallFilter($kernel);
    }

    /**
     * @param array<string, mixed> $arguments
     */
    private function createDefinitionCallForFoundryContext(array $arguments): DefinitionCall
    {
        $reflection = new \ReflectionMethod(FoundryContext::class, 'createObjectWithProperties');

        $definition = $this->createStub(Definition::class);
        $definition->method('getReflection')->willReturn($reflection);

        $environment = $this->createStub(Environment::class);
        $feature = $this->createStub(FeatureNode::class);
        $step = $this->createStub(StepNode::class);

        return new DefinitionCall(
            $environment, $feature, $step, $definition, $arguments
        );
    }
}

class TestEntity
{
    public function __construct(
        public int $id, public string $name,
    ) {
    }
}

class DatedEntity
{
    public function __construct(
        public int $id, public \DateTimeImmutable $createdAt,
    ) {
    }
}

class EnumEntity
{
    public function __construct(
        public int $id, public TestStatus $status, public TestPriority $priority,
    ) {
    }
}

class RelationEntity
{
    public function __construct(
        public int $id, public TestEntity $relation,
    ) {
    }
}

abstract class ParentEntity
{
    public string $inheritedProperty = '';
}

class ChildEntity extends ParentEntity
{
    public function __construct(
        public int $id,
    ) {
    }
}

enum TestStatus: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
}

enum TestPriority: int
{
    case LOW = 1;
    case MEDIUM = 2;
    case HIGH = 3;
}

/** @extends ObjectFactory<TestEntity> */
#[FactoryShortName('test entity')]
final class TestEntityFactory extends ObjectFactory
{
    public static function class(): string
    {
        return TestEntity::class;
    }

    protected function defaults(): array
    {
        return ['id' => 1, 'name' => 'Test'];
    }
}

/** @extends ObjectFactory<DatedEntity> */
#[FactoryShortName('dated entity')]
final class DatedEntityFactory extends ObjectFactory
{
    public static function class(): string
    {
        return DatedEntity::class;
    }

    protected function defaults(): array
    {
        return ['id' => 1, 'createdAt' => new \DateTimeImmutable()];
    }
}

/** @extends ObjectFactory<EnumEntity> */
#[FactoryShortName('enum entity')]
final class EnumEntityFactory extends ObjectFactory
{
    public static function class(): string
    {
        return EnumEntity::class;
    }

    protected function defaults(): array
    {
        return ['id' => 1, 'status' => TestStatus::ACTIVE, 'priority' => TestPriority::LOW];
    }
}

/** @extends ObjectFactory<RelationEntity> */
#[FactoryShortName('relation entity')]
final class RelationEntityFactory extends ObjectFactory
{
    public static function class(): string
    {
        return RelationEntity::class;
    }

    protected function defaults(): array
    {
        return ['id' => 1, 'relation' => new TestEntity(id: 0, name: 'default')];
    }
}

/** @extends ObjectFactory<ChildEntity> */
#[FactoryShortName('child entity')]
final class ChildEntityFactory extends ObjectFactory
{
    public static function class(): string
    {
        return ChildEntity::class;
    }

    protected function defaults(): array
    {
        return ['id' => 1, 'inheritedProperty' => 'default'];
    }
}
