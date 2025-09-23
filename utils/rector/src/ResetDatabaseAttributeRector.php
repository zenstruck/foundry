<?php

namespace Zenstruck\Foundry\Utils\Rector;

use PhpParser\Node;
use PhpParser\NodeFinder;
use Rector\Rector\AbstractRector;
use Zenstruck\Foundry\Attribute\ResetDatabase as ResetDatabaseAttribute;
use Zenstruck\Foundry\Test\ResetDatabase as ResetDatabaseTrait;

final class ResetDatabaseAttributeRector extends AbstractRector
{
    public function __construct(
        private readonly NodeFinder $nodeFinder,
    ) {
    }

    /** @return array<class-string<Node>> */
    public function getNodeTypes(): array
    {
        return [Node\Stmt\Class_::class];
    }

    /** @param Node\Stmt\Class_ $node */
    public function refactor(Node $node): Node|null
    {
        /** @var ?Node\Stmt\TraitUse $traitUseWithResetDatabase */
        $traitUseWithResetDatabase = $this->nodeFinder->findFirst($node->stmts, function (Node $node): bool {
            return $node instanceof Node\Stmt\TraitUse
                && array_any($node->traits, fn(Node\Name $name) => $this->getName($name) === ResetDatabaseTrait::class);
        });

        if (!$traitUseWithResetDatabase) {
            return null;
        }

        $traitUseWithResetDatabase->traits = array_filter(
            $traitUseWithResetDatabase->traits,
            fn(Node\Name $name) => $this->getName($name) !== ResetDatabaseTrait::class
        );

        if ($traitUseWithResetDatabase->traits === []) {
            $node->stmts = array_filter($node->stmts, fn(Node\Stmt $stmt) => $stmt !== $traitUseWithResetDatabase);
        }

        $hasResetDatabaseTrait = (bool)$this->nodeFinder->findFirst($node->attrGroups, function (Node $node): bool {
            return $this->getName($node) === ResetDatabaseAttribute::class;
        });

        if ($hasResetDatabaseTrait) {
            return $node;
        }

        $node->attrGroups[] = new Node\AttributeGroup([
            new Node\Attribute(new Node\Name\FullyQualified(ResetDatabaseAttribute::class)),
        ]);

        return $node;
    }
}
