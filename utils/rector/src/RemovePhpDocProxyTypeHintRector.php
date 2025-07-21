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
use PHPStan\PhpDoc\TypeStringResolver;
use PHPStan\PhpDocParser\Ast\PhpDoc\MethodTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\ParamTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\ReturnTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\VarTagValueNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeTraverser;
use PHPStan\Type\VerbosityLevel;
use Rector\BetterPhpDocParser\PhpDocInfo\PhpDocInfo;
use Rector\BetterPhpDocParser\PhpDocInfo\PhpDocInfoFactory;
use Rector\Comments\NodeDocBlock\DocBlockUpdater;
use Rector\Rector\AbstractRector;
use Rector\StaticTypeMapper\Naming\NameScopeFactory;
use Rector\StaticTypeMapper\StaticTypeMapper;
use Rector\StaticTypeMapper\ValueObject\Type\NonExistingObjectType;
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
            Node\Stmt\Class_::class,
            Node\Stmt\Expression::class,
        ];
    }

    public function refactor(Node $node): ?Node
    {
        $phpDocInfo = $this->phpDocInfoFactory->createFromNodeOrEmpty($node);

        $nameScope = $this->nameScopeFactory->createNameScopeFromNodeWithoutTemplateTypes($node);

        $returnTypeChanged = $this->handleReturnType($phpDocInfo, $nameScope);
        $paramTypeChanged = $this->handleParameterTypes($phpDocInfo, $nameScope);
        $methodTypeChanged = $this->handleMethodTypes($phpDocInfo, $nameScope);
        $varTypeChanged = $this->handleVarTypes($phpDocInfo, $nameScope);

        if ($returnTypeChanged || $paramTypeChanged || $methodTypeChanged || $varTypeChanged) {
            $this->docBlockUpdater->updateRefactoredNodeWithPhpDocInfo($node);

            return $node;
        };

        return null;
    }

    private function handleReturnType(PhpDocInfo $phpDocInfo, NameScope $nameScope): bool
    {
        $returnTag = $phpDocInfo->getReturnTagValue();

        if (!$returnTag) {
            return false;
        }

        return $this->handleTag($returnTag, $nameScope);
    }

    private function handleParameterTypes(PhpDocInfo $phpDocInfo, NameScope $nameScope): bool
    {
        $paramTags = $phpDocInfo->getParamTagValueNodes();

        $nodeChanged = false;
        foreach ($paramTags as $paramTag) {
            if ($this->handleTag($paramTag, $nameScope)) {
                $nodeChanged = true;
            }
        }

        return $nodeChanged;
    }

    private function handleMethodTypes(PhpDocInfo $phpDocInfo, NameScope $nameScope): bool
    {
        $methodTags = $phpDocInfo->getPhpDocNode()->getMethodTagValues();

        $nodeChanged = false;
        foreach ($methodTags as $methodTag) {
            if ($this->handleTag($methodTag, $nameScope)) {
                $nodeChanged = true;
            }
        }

        return $nodeChanged;
    }

    private function handleVarTypes(PhpDocInfo $phpDocInfo, NameScope $nameScope): bool
    {
        $varTags = $phpDocInfo->getPhpDocNode()->getVarTagValues();

        $nodeChanged = false;
        foreach ($varTags as $varTag) {
            if ($this->handleTag($varTag, $nameScope)) {
                $nodeChanged = true;
            }
        }

        return $nodeChanged;
    }

    private function handleTag(ParamTagValueNode|ReturnTagValueNode|MethodTagValueNode|VarTagValueNode $docNode, NameScope $nameScope): bool
    {
        if ($docNode instanceof MethodTagValueNode) {
            if ($docNode->returnType === null) {
                return false;
            }

            $typeProperty = 'returnType';
        } else {
            $typeProperty = 'type';
        }

        // @phpstan-ignore property.notFound,property.notFound
        $tagType = $this->typeNodeResolver->resolve($docNode->$typeProperty, $nameScope);

        // prevent to change something when Proxy is not part of the type found
        if (!str_contains($tagType->describe(VerbosityLevel::value()), 'Proxy')) {
            return false;
        }

        if ($tagType->isArray()->yes()) {
            $arrayType = TypeTraverser::map($tagType, function (Type $type, callable $traverse): Type {
                if ($type instanceof GenericObjectType && $type->getClassName() === Proxy::class
                ) {
                    if (!$this->reflectionProvider->hasClass($type->getTypes()[0]->getObjectClassNames()[0])) {
                        return new NonExistingObjectType($type->getTypes()[0]->getObjectClassNames()[0]);
                    }

                    return new ObjectType($type->getTypes()[0]->getObjectClassNames()[0]);
                }

                return $traverse($type);
            });

            // @phpstan-ignore property.notFound,property.notFound
            $docNode->$typeProperty = $this->staticTypeMapper->mapPHPStanTypeToPHPStanPhpDocTypeNode($arrayType);

            return true;
        }

        if (!(new ObjectType(Proxy::class))->accepts($tagType, true)->yes()) {
            return false;
        }

        preg_match('/<([^>]+)>/', $tagType->describe(VerbosityLevel::typeOnly()), $matches);

        if (!isset($matches[1])) {
            return false;
        }

        $type = $this->typeStringResolver->resolve($matches[1]);

        $typeNode = $this->staticTypeMapper->mapPHPStanTypeToPHPStanPhpDocTypeNode($type);

        if ($typeNode instanceof IdentifierTypeNode && !$this->reflectionProvider->hasClass($typeNode->name)) {
            // if class does not exist, it's most likely a generic type, so we remove the leading backslash
            $typeNode = new IdentifierTypeNode(ltrim($typeNode->name, '\\'));
        }

        // @phpstan-ignore property.notFound,property.notFound
        $docNode->$typeProperty = $typeNode;

        return true;
    }
}
