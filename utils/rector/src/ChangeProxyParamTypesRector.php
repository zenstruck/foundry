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
use PHPStan\Analyser\NameScope;
use PHPStan\PhpDoc\TypeNodeResolver;
use PHPStan\PhpDocParser\Ast\PhpDoc\ParamTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTagNode;
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

final class ChangeProxyParamTypesRector extends AbstractRector
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
        return [
            Node\Stmt\ClassMethod::class,
            Node\Stmt\Function_::class,
            Node\Expr\Closure::class,
            Node\Expr\ArrowFunction::class,
        ];
    }

    /**
     * @param Node\Stmt\ClassMethod|Node\Stmt\Function_|Node\Expr\Closure|Node\Expr\ArrowFunction $node
     */
    public function refactor(Node $node): ?Node
    {
        $phpDocInfo = $this->phpDocInfoFactory->createFromNodeOrEmpty($node);
        $phpDocNode = $phpDocInfo->getPhpDocNode();

        $nameScope = $this->nameScopeFactory->createNameScopeFromNodeWithoutTemplateTypes($node);

        $changed = false;

        foreach ($node->params as $param) {
            if ($param->variadic) {
                // we don't handle variadic parameter
                continue;
            }

            if (!$param->var instanceof Node\Expr\Variable || !is_string($param->var->name)) {
                continue;
            }

            $paramName = $param->var->name;

            if (
                ($targetClassType = $this->getParamTargetClass($paramName, $phpDocNode, $nameScope)) === null
            ) {
                if ($param->type instanceof Node\Name && $param->type->name === Proxy::class) {
                    $param->type = new Node\Name('object');

                    $changed = true;
                }

                continue;
            }

            $targetClassName = $targetClassType->getObjectClassNames()[0];

            if ($this->reflectionProvider->hasClass($targetClassName)) {
                $targetClassName = '\\' . $targetClassName;
                $param->type = new Node\Name($targetClassName);
            } else {
                // if the target classe name does not exist, it is likely a generic type
                // so we should remove the return type
                $param->type = new Node\Name('object');
            }

            foreach ($this->getParamTagNodes($paramName, $phpDocNode) as $returnTagNode) {
                $this->phpDocTagRemover->removeTagValueFromNode($phpDocInfo, $returnTagNode);
            }

            $phpDocInfo->addPhpDocTagNode(
                new PhpDocTagNode(
                    '@param',
                    new ParamTagValueNode(
                        type: new IdentifierTypeNode($targetClassName),
                        isVariadic: false,
                        parameterName: "\$$paramName",
                        description: '',
                        isReference: $param->byRef
                    )
                )
            );
            $this->docBlockUpdater->updateRefactoredNodeWithPhpDocInfo($node);
        }

        return $changed ? $node : null;
    }

    private function getParamTargetClass(string $paramName, PhpDocNode $docNode, NameScope $nameScope): ?Type
    {
        $paramNode = $this->getParamTagNodes($paramName, $docNode)[0] ?? null;

        if ($paramNode === null) {
            return null;
        }

        if ($this->isProxyGenericTypeNode($paramNode->type)) {
            return $this->resolveTargetClassTypeFromTypeNode($paramNode->type->genericTypes[0], $nameScope);
        }

        if ($paramNode->type instanceof IntersectionTypeNode) {
            foreach ($paramNode->type->types as $typeInIntersection) {
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
     * @return ParamTagValueNode[]
     */
    private function getParamTagNodes(string $paramName, PhpDocNode $docNode): array
    {
        $paramPhpDocNodes = array_values([
            ...$docNode->getParamTagValues('@phpstan-param'),
            ...$docNode->getParamTagValues('@psalm-param'),
            ...$docNode->getParamTagValues(),
        ]);

        return array_filter(
            $paramPhpDocNodes,
            static fn(ParamTagValueNode $n) => $n->parameterName === "\$$paramName"
        );
    }
}
