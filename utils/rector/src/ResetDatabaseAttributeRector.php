<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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
    public function refactor(Node $node): ?Node
    {
        /** @var ?Node\Stmt\TraitUse $traitUseWithResetDatabase */
        $traitUseWithResetDatabase = $this->nodeFinder->findFirst($node->stmts, function(Node $node): bool {
            return $node instanceof Node\Stmt\TraitUse
                && array_any($node->traits, fn(Node\Name $name) => ResetDatabaseTrait::class === $this->getName($name));
        });

        if (!$traitUseWithResetDatabase) {
            return null;
        }

        $traitUseWithResetDatabase->traits = \array_filter(
            $traitUseWithResetDatabase->traits,
            fn(Node\Name $name) => ResetDatabaseTrait::class !== $this->getName($name)
        );

        if ([] === $traitUseWithResetDatabase->traits) {
            $node->stmts = \array_filter($node->stmts, fn(Node\Stmt $stmt) => $stmt !== $traitUseWithResetDatabase);
        }

        $hasResetDatabaseTrait = (bool) $this->nodeFinder->findFirst($node->attrGroups, function(Node $node): bool {
            return ResetDatabaseAttribute::class === $this->getName($node);
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
