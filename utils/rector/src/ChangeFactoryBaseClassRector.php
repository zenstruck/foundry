<?php

declare(strict_types=1);

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Utils\Rector;

use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocNode;
use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PhpParser\NodeFinder;
use PHPStan\Analyser\MutatingScope;
use PHPStan\PhpDocParser\Ast\PhpDoc\ExtendsTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTagNode;
use PHPStan\PhpDocParser\Ast\Type\GenericTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\Type\ObjectType;
use Rector\BetterPhpDocParser\PhpDocInfo\PhpDocInfoFactory;
use Rector\BetterPhpDocParser\PhpDocManipulator\PhpDocTagRemover;
use Rector\BetterPhpDocParser\ValueObject\Type\FullyQualifiedIdentifierTypeNode;
use Rector\Comments\NodeDocBlock\DocBlockUpdater;
use Rector\NodeTypeResolver\Node\AttributeKey;
use Rector\Rector\AbstractRector;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

final class ChangeFactoryBaseClassRector extends AbstractRector
{
    public function __construct(
        private readonly PhpDocTagRemover $phpDocTagRemover,
        private readonly PhpDocInfoFactory $phpDocInfoFactory,
        private readonly DocBlockUpdater $docBlockUpdater,
        private readonly NodeFinder  $nodeFinder,

    ) {
    }

    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [Class_::class];
    }

    /**
     * @param Class_ $node
     */
    public function refactor(Node $node): ?Node
    {
        /** @var \PHPStan\Analyser\Scope|null $scope */
        $scope = $node->getAttribute(AttributeKey::SCOPE);

        if (!($reflection = $scope?->getClassReflection())
            || $reflection->getParentClass()?->getName() !== PersistentProxyObjectFactory::class) {
            return null;
        }

        $node->extends = new Node\Name\FullyQualified(PersistentObjectFactory::class);
        $this->updateExtendsPhpDoc($node);

        return $node;
    }

    private function updateExtendsPhpDoc(Class_ $node): void
    {
        $phpDocInfo = $this->phpDocInfoFactory->createFromNodeOrEmpty($node);
        $phpDocNode = $phpDocInfo->getPhpDocNode();

        $targetClass = $this->extractTargetClass($node, $phpDocNode);

        if (!$targetClass) {
            return;
        }

        $extendsPhpDocNodes = [
            ...$phpDocNode->getExtendsTagValues(),
            ...$phpDocNode->getExtendsTagValues('@phpstan-extends'),
            ...$phpDocNode->getExtendsTagValues('@psalm-extends'),
        ];

        // first, remove all @extends tags
        foreach ($extendsPhpDocNodes as $extendsPhpDocNode) {
            $this->phpDocTagRemover->removeTagValueFromNode($phpDocInfo, $extendsPhpDocNode);
        }

        // then rewrite the good one
        $phpDocInfo->addPhpDocTagNode(
            new PhpDocTagNode(
                '@extends',
                new ExtendsTagValueNode(
                    type: new GenericTypeNode(
                        new FullyQualifiedIdentifierTypeNode(PersistentObjectFactory::class),
                        [$targetClass]
                    ),
                    description: ''
                )
            )
        );

        $this->docBlockUpdater->updateRefactoredNodeWithPhpDocInfo($node);
    }

    private function extractTargetClass(Class_ $node, PhpDocNode $phpDocNode): ?TypeNode
    {
        $extendsPhpDocNodes = array_values([
            ...$phpDocNode->getExtendsTagValues(),
            ...$phpDocNode->getExtendsTagValues('@phpstan-extends'),
            ...$phpDocNode->getExtendsTagValues('@psalm-extends'),
        ]);

        if (isset($extendsPhpDocNodes[0])
            && isset($extendsPhpDocNodes[0]->type->genericTypes[0])
            && $extendsPhpDocNodes[0]->type->genericTypes[0] instanceof IdentifierTypeNode
        ) {
            return $extendsPhpDocNodes[0]->type->genericTypes[0];
        }

        /** @var Node\Stmt\ClassMethod|null $classMethod */
        $classMethod = $this->nodeFinder->findFirst($node->stmts, function (Node $node): bool {
            return $node instanceof Node\Stmt\ClassMethod
                && $this->getName($node) === 'class';
        });

        if (!$classMethod || !$classMethod->stmts) {
            return null;
        }

        $returnStatement = $this->nodeFinder->findFirstInstanceOf($classMethod->stmts, Node\Stmt\Return_::class);

        if (!$returnStatement || !$returnStatement->expr instanceof Node\Expr\ClassConstFetch) {
            return null;
        }

        $name = $this->getName($returnStatement->expr->class);

        return new FullyQualifiedIdentifierTypeNode("\\$name");
    }
}
