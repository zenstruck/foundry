<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Test\Behat;

use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;
use Behat\Transformation\Transform;

/**
 * Built-in transformations resolving <foundry:lastId(...)> and <foundry:id(...)>
 * placeholders in step arguments, including PyString and table arguments.
 *
 * The composing class only needs to implement FoundryContextInterface; the ObjectRegistry is
 * injected by the HasObjectRegistry trait (see FoundryPlaceholderContext).
 *
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 * @experimental
 */
trait PlaceholderTransforms
{
    use HasObjectRegistry;

    #[Transform('/(.*)<foundry:lastId\(\s*([^)]+?)\s*\)>(.*)/')]
    public function transformLastIdForSpecificObject(string $before, string $factoryShortName, string $after): string
    {
        return $this->objectRegistry->resolveIdPlaceholders("{$before}<foundry:lastId({$factoryShortName})>{$after}");
    }

    #[Transform('/(.*)<foundry:id\(\s*([^,)]+?)\s*,\s*([^)]+?)\s*\)>(.*)/')]
    public function transformIdForSpecificObject(string $before, string $factoryShortName, string $objectName, string $after): string
    {
        return $this->objectRegistry->resolveIdPlaceholders("{$before}<foundry:id({$factoryShortName}, {$objectName})>{$after}");
    }

    /**
     * By-type transformation (no pattern): applied to every PyString argument of a step
     * definition whose parameter is type-hinted PyStringNode.
     */
    #[Transform]
    public function transformIdPlaceholdersInPyStrings(PyStringNode $pyString): PyStringNode
    {
        $strings = $pyString->getStrings();
        $resolved = \array_map($this->objectRegistry->resolveIdPlaceholders(...), $strings);

        return $resolved === $strings ? $pyString : new PyStringNode($resolved, $pyString->getLine());
    }

    /**
     * By-type transformation (no pattern): applied to every table argument of a step
     * definition whose parameter is type-hinted TableNode.
     */
    #[Transform]
    public function transformIdPlaceholdersInTables(TableNode $table): TableNode
    {
        $rows = $table->getTable();
        $resolved = \array_map(
            fn(array $row) => \array_map($this->objectRegistry->resolveIdPlaceholders(...), $row),
            $rows,
        );

        return $resolved === $rows ? $table : new TableNode($resolved);
    }
}
