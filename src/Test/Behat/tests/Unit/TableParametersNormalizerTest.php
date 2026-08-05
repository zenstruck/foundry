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

use Behat\Gherkin\Node\TableNode;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Zenstruck\Foundry\ObjectFactory;
use Zenstruck\Foundry\Persistence\IdentifierResolver;
use Zenstruck\Foundry\Test\Behat\Attribute\FactoryShortName;
use Zenstruck\Foundry\Test\Behat\Exception\InvalidObjectParameter;
use Zenstruck\Foundry\Test\Behat\FactoryShortNameResolver;
use Zenstruck\Foundry\Test\Behat\ObjectRegistry;
use Zenstruck\Foundry\Test\Behat\TableParametersNormalizer;

final class TableParametersNormalizerTest extends TestCase
{
    private TableParametersNormalizer $normalizer;
    private ObjectRegistry $objectRegistry;

    protected function setUp(): void
    {
        $factoryResolver = new FactoryShortNameResolver([
            new TestEntityFactory(),
            new DatedEntityFactory(),
            new EnumEntityFactory(),
            new RelationEntityFactory(),
            new ChildEntityFactory(),
        ]);
        $this->objectRegistry = new ObjectRegistry(
            $factoryResolver, $this->createStub(IdentifierResolver::class)
        );
        $this->objectRegistry->reset();

        $this->normalizer = new TableParametersNormalizer($factoryResolver, $this->objectRegistry);
    }

    #[Test]
    #[DataProvider('tableNormalizationProvider')]
    public function it_normalizes_table_values(array $input, array $expected): void
    {
        $rows = $this->normalizer->normalize($this->createTableNodeFromRow($input), 'test entity');

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
    public function it_normalizes_multiple_rows(): void
    {
        $rows = $this->normalizer->normalize(
            new TableNode([
                ['name', '_ref'],
                ['John', 'A'],
                ['Jane', 'B'],
            ]),
            'test entity',
        );

        self::assertSame([
            ['name' => 'John', '_ref' => 'A'],
            ['name' => 'Jane', '_ref' => 'B'],
        ], $rows);
    }

    #[Test]
    public function it_normalizes_object_reference_with_explicit_factory(): void
    {
        $referencedObject = new TestEntity(id: 1, name: 'Referenced');
        $this->objectRegistry->store($referencedObject, 'the-ref');

        $rows = $this->normalizer->normalize(
            $this->createTableNodeFromRow(['relation' => '<foundry:object(test entity, the-ref)>']),
            'test entity',
        );

        self::assertSame($referencedObject, $rows[0]['relation']);
    }

    #[Test]
    public function it_throws_on_invalid_object_reference(): void
    {
        $this->expectException(InvalidObjectParameter::class);
        $this->expectExceptionMessage('A reference to an object cannot be resolved in the table, at column "relation"');

        $this->normalizer->normalize(
            $this->createTableNodeFromRow(['relation' => '<foundry:object(test entity, nonexistent)>']),
            'test entity',
        );
    }

    #[Test]
    public function it_normalizes_date_value(): void
    {
        $rows = $this->normalizer->normalize(
            $this->createTableNodeFromRow(['createdAt' => '2024-01-15 10:30:00']),
            'dated entity',
        );

        self::assertInstanceOf(\DateTimeImmutable::class, $rows[0]['createdAt']);
        self::assertSame('2024-01-15 10:30:00', $rows[0]['createdAt']->format('Y-m-d H:i:s'));
    }

    #[Test]
    public function it_throws_on_invalid_date_value(): void
    {
        $this->expectException(InvalidObjectParameter::class);
        $this->expectExceptionMessage('Invalid date given "not-a-date", at column "createdAt"');

        $this->normalizer->normalize(
            $this->createTableNodeFromRow(['createdAt' => 'not-a-date']),
            'dated entity',
        );
    }

    #[Test]
    public function it_normalizes_string_enum_value(): void
    {
        $rows = $this->normalizer->normalize(
            $this->createTableNodeFromRow(['status' => 'active']),
            'enum entity',
        );

        self::assertSame(TestStatus::ACTIVE, $rows[0]['status']);
    }

    #[Test]
    public function it_normalizes_int_enum_value(): void
    {
        $rows = $this->normalizer->normalize(
            $this->createTableNodeFromRow(['priority' => '2']),
            'enum entity',
        );

        self::assertSame(TestPriority::MEDIUM, $rows[0]['priority']);
    }

    #[Test]
    public function it_throws_on_invalid_enum_value(): void
    {
        $this->expectException(InvalidObjectParameter::class);
        $this->expectExceptionMessage('Invalid enum value given "invalid_status", at column "status"');

        $this->normalizer->normalize(
            $this->createTableNodeFromRow(['status' => 'invalid_status']),
            'enum entity',
        );
    }

    #[Test]
    public function it_normalizes_object_by_type_inference(): void
    {
        $referencedEntity = new TestEntity(id: 1, name: 'Referenced');
        $this->objectRegistry->store($referencedEntity, 'the-relation');

        $rows = $this->normalizer->normalize(
            $this->createTableNodeFromRow(['relation' => 'the-relation']),
            'relation entity',
        );

        self::assertSame($referencedEntity, $rows[0]['relation']);
    }

    #[Test]
    public function it_handles_inherited_property_from_parent_class(): void
    {
        $rows = $this->normalizer->normalize(
            $this->createTableNodeFromRow(['inheritedProperty' => 'value']),
            'child entity',
        );

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
