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

use PhpParser\Node;
use PhpParser\Node\FunctionLike;
use PHPStan\Analyser\NameScope;
use PHPStan\PhpDoc\TypeNodeResolver;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTagNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\ReturnTagValueNode;
use PHPStan\PhpDocParser\Ast\Type\GenericTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IntersectionTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\Type;
use Rector\BetterPhpDocParser\PhpDocInfo\PhpDocInfoFactory;
use Rector\BetterPhpDocParser\PhpDocManipulator\PhpDocTagRemover;
use Rector\Comments\NodeDocBlock\DocBlockUpdater;
use Rector\Rector\AbstractRector;
use Rector\StaticTypeMapper\Naming\NameScopeFactory;
use Zenstruck\Foundry\Persistence\Proxy;

final class ChangeProxyReturnTypesRector extends AbstractRector
{
    public function __construct(
        private readonly PhpDocInfoFactory $phpDocInfoFactory,
        private readonly PhpDocTagRemover $phpDocTagRemover,
        private readonly DocBlockUpdater $docBlockUpdater,
        private readonly TypeNodeResolver $typeNodeResolver,
        private readonly NameScopeFactory $nameScopeFactory,
        private readonly ReflectionProvider $reflectionProvider,
    ) {
    }

    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [Node\Stmt\ClassMethod::class, Node\Stmt\Function_::class, Node\Expr\Closure::class, Node\Expr\ArrowFunction::class, ];
    }

    /**
     * @param Node\Stmt\ClassMethod|Node\Stmt\Function_|Node\Expr\Closure|Node\Expr\ArrowFunction $node
     */
    public function refactor(Node $node): ?Node
    {
        $phpDocInfo = $this->phpDocInfoFactory->createFromNodeOrEmpty($node);
        $phpDocNode = $phpDocInfo->getPhpDocNode();

        $nameScope = $this->nameScopeFactory->createNameScopeFromNodeWithoutTemplateTypes($node);

        if (
            ($targetClassType = $this->getTargetClass($phpDocNode, $nameScope)) === null
        ) {
            if ($node->returnType instanceof Node\Name && $node->returnType->name === Proxy::class) {
                $node->returnType = new Node\Name('object');

                return $node;
            }

            return null;
        }

        $targetClassName = $targetClassType->getObjectClassNames()[0];

        if ($this->reflectionProvider->hasClass($targetClassName)) {
            $targetClassName = '\\' . $targetClassName;
            $node->returnType = new Node\Name($targetClassName);
        } else {
            // if the target classe name does not exist, it is likely a generic type
            // so we should remove the return type
            $node->returnType = new Node\Name('object');
        }

        foreach ($this->getReturnTagNodes($phpDocNode) as $returnTagNode) {
            $this->phpDocTagRemover->removeTagValueFromNode($phpDocInfo, $returnTagNode);
        }

        $phpDocInfo->addPhpDocTagNode(
            new PhpDocTagNode(
                '@return',
                new ReturnTagValueNode(
                    type: new IdentifierTypeNode($targetClassName),
                    description: ''
                )
            )
        );
        $this->docBlockUpdater->updateRefactoredNodeWithPhpDocInfo($node);

        return $node;
    }

    private function getTargetClass(PhpDocNode $docNode, NameScope $nameScope): ?Type
    {
        $returnNode = $this->getReturnTagNodes($docNode)[0] ?? null;

        if ($returnNode === null) {
            return null;
        }

        if ($this->isProxyGenericTypeNode($returnNode->type)) {
            return $this->resolveTargetClassTypeFromTypeNode($returnNode->type->genericTypes[0], $nameScope);
        }

        if ($returnNode->type instanceof IntersectionTypeNode) {
            foreach ($returnNode->type->types as $typeInIntersection) {
                if ($this->isProxyGenericTypeNode($typeInIntersection)) {
                    return $this->resolveTargetClassTypeFromTypeNode($typeInIntersection->genericTypes[0], $nameScope);
                }
            }
        }

        return null;
    }

    /**
     * @phpstan-assert-if-true GenericTypeNode $typeNode
     */
    private function isProxyGenericTypeNode(TypeNode $typeNode): bool
    {
        if (!$typeNode instanceof GenericTypeNode) {
            return false;
        }

        return ($typeNode->type->name === Proxy::class || $typeNode->type->name === 'Proxy')
            && count($typeNode->genericTypes) === 1
            && $typeNode->genericTypes[0] instanceof IdentifierTypeNode;
    }

    private function resolveTargetClassTypeFromTypeNode(
        TypeNode $typeNode,
        NameScope $nameScope
    ): ?Type {
        $type = $this->typeNodeResolver->resolve($typeNode, $nameScope);

        if (!$type->isObject()->yes()) {
            return null;
        }

        return $type;
    }

    /**
     * @return ReturnTagValueNode[]
     */
    private function getReturnTagNodes(PhpDocNode $docNode): array
    {
        return array_values([
            ...$docNode->getReturnTagValues('@phpstan-return'),
            ...$docNode->getReturnTagValues('@psalm-return'),
            ...$docNode->getReturnTagValues(),
        ]);
    }
}
