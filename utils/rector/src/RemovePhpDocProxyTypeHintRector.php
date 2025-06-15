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
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NameScope;
use PHPStan\PhpDoc\TypeNodeResolver;
use PHPStan\PhpDoc\TypeStringResolver;
use PHPStan\PhpDocParser\Ast\PhpDoc\ParamTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\ReturnTagValueNode;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeTraverser;
use PHPStan\Type\VerbosityLevel;
use Rector\BetterPhpDocParser\PhpDocInfo\PhpDocInfo;
use Rector\BetterPhpDocParser\PhpDocInfo\PhpDocInfoFactory;
use Rector\Comments\NodeDocBlock\DocBlockUpdater;
use Rector\NodeTypeResolver\Node\AttributeKey;
use Rector\Rector\AbstractRector;
use Rector\StaticTypeMapper\Naming\NameScopeFactory;
use Rector\StaticTypeMapper\StaticTypeMapper;
use Zenstruck\Foundry\Persistence\Proxy;

/**
 * Remove all Proxy type hints from PHPDoc.
 */
final class RemovePhpDocProxyTypeHintRector extends AbstractRector
{
    public function __construct(
        private readonly PhpDocInfoFactory $phpDocInfoFactory,
        private readonly TypeStringResolver $typeStringResolver,
        private readonly StaticTypeMapper $staticTypeMapper,
        private readonly NameScopeFactory $nameScopeFactory,
        private readonly TypeNodeResolver $typeNodeResolver,
        private readonly DocBlockUpdater $docBlockUpdater,
    ) {
    }

    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [Node\FunctionLike::class];
    }

    /**
     * @param Node\FunctionLike $node
     */
    public function refactor(Node $node): ?Node
    {
        if (!$node instanceof Node\Stmt\ClassMethod && !$node instanceof Node\Stmt\Function_) {
            return null;
        }

        $phpDocInfo = $this->phpDocInfoFactory->createFromNodeOrEmpty($node);

        $nameScope = $this->nameScopeFactory->createNameScopeFromNodeWithoutTemplateTypes($node);

        $this->handleReturnType($phpDocInfo, $nameScope);
        $this->handleParameterTypes($phpDocInfo, $nameScope);

        $this->docBlockUpdater->updateRefactoredNodeWithPhpDocInfo($node);

        return $node;
    }

    private function handleReturnType(PhpDocInfo $phpDocInfo, NameScope $nameScope): void
    {
        $returnTag = $phpDocInfo->getReturnTagValue();

        if (!$returnTag) {
            return;
        }

        $this->handleTag($returnTag, $nameScope);
    }

    private function handleParameterTypes(PhpDocInfo $phpDocInfo, NameScope $nameScope): void
    {
        $paramTags = $phpDocInfo->getParamTagValueNodes();

        foreach ($paramTags as $paramTag) {
            $this->handleTag($paramTag, $nameScope);
        }
    }

    private function handleTag(ParamTagValueNode|ReturnTagValueNode $tagValueNode, NameScope $nameScope): void
    {
        $tagType = $this->typeNodeResolver->resolve($tagValueNode->type, $nameScope);

        if ($tagType->isArray()->yes()) {
            $arrayType = TypeTraverser::map($tagType, function (Type $type, callable $traverse): Type {
                if ($type instanceof GenericObjectType
                    && $type->getClassName() === Proxy::class
                ) {
                    return new ObjectType($type->getTypes()[0]->getObjectClassNames()[0]);
                }

                return $traverse($type);
            });

            $tagValueNode->type = $this->staticTypeMapper->mapPHPStanTypeToPHPStanPhpDocTypeNode($arrayType);

            return;
        }

        if (!(new ObjectType(Proxy::class))->accepts($tagType, true)->yes()) {
            return;
        }

        preg_match('/<([^>]+)>/', $tagType->describe(VerbosityLevel::typeOnly()), $matches);

        if (!isset($matches[1])) {
            return;
        }

        $type = $this->typeStringResolver->resolve($matches[1]);

        $tagValueNode->type = $this->staticTypeMapper->mapPHPStanTypeToPHPStanPhpDocTypeNode($type);
    }
}
