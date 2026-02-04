<?php

namespace Zenstruck\Foundry\Test\Behat;

use Behat\Gherkin\Node\TableNode;
use function Zenstruck\Foundry\set;

/**
 * @internal
 *
 * @method list<array<string, mixed>> getHash()
 * @method list<array<string, mixed>> getColumnsHash()
 * @method list<string> getColumn(int $index)
 * @method list<list<mixed>> getRows()
 * @method list<mixed> getRow(int $index)
 * @method array<int, list<mixed>> getTable()
 * @method array<string, string|list<mixed>> getRowsHash()
 * @method list<mixed> getRowsHash()
 */
final class FoundryTableNode extends TableNode
{
    /** @var array<array-key, int> */
    private array $maxLineLength = [];

    private FactoryShortNameResolver $factoryShortNameResolver; // @phpstan-ignore property.uninitialized
    private ObjectRegistry $objectRegistry; // @phpstan-ignore property.uninitialized

    /**
     * @param array<array-key, int> $maxLineLength
     * @param array<int, list<mixed>> $table
     */
    public static function create(
        FactoryShortNameResolver $factoryShortNameResolver,
        ObjectRegistry $objectRegistry,
        array $maxLineLength,
        array $table
    ): static {
        // let's neutralize the table node constructor: it checks if the table contains only scalar values,
        // but we want our TableNode to carry objects

        // This is super hacky but seems to work well

        // All other sanity checks performed by the constructor
        // are not relevant, since they have already been performed previously

        $tableNode = (new \ReflectionClass(self::class))->newInstanceWithoutConstructor();
        set($tableNode, 'table', $table);

        $tableNode->factoryShortNameResolver = $factoryShortNameResolver;
        $tableNode->objectRegistry = $objectRegistry;
        $tableNode->maxLineLength = $maxLineLength;

        return $tableNode;
    }

    public function getRowAsString($rowNum): string
    {
        $values = [];
        foreach ($this->getRow($rowNum) as $column => $value) {
            $values[] = $this->padRight(' ' . $this->getValueAsString($value) . ' ', $this->maxLineLength[$column] + 2);
        }

        return sprintf('|%s|', implode('|', $values));
    }

    public function getRowAsStringWithWrappedValues($rowNum, $wrapper): string
    {
        $values = [];
        foreach ($this->getRow($rowNum) as $column => $value) {
            $value = $this->padRight(' ' . $this->getValueAsString($value) . ' ', $this->maxLineLength[$column] + 2);

            $values[] = call_user_func($wrapper, $value, $column);
        }

        return sprintf('|%s|', implode('|', $values));
    }

    private function getValueAsString(mixed $value): string
    {
        return match (true) {
            !is_object($value) => (string)$value,
            $value instanceof \DateTimeInterface => $value->format('Y-m-d H:i:s'),
            $value instanceof \BackedEnum => (string)$value->value,
            $this->objectRegistry->isStored($value) => "{$this->factoryShortNameResolver->getShortNameForClass($value::class)} {$this->objectRegistry->getNameFor($value)}",
            default => throw new \LogicException("Unsupported value type: ".\get_debug_type($value)),
        };
    }
}
