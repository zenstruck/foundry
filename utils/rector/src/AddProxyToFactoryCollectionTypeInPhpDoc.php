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
use PHPStan\PhpDocParser\Ast\PhpDoc\ParamTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTagNode;
use PHPStan\PhpDocParser\Ast\Type\GenericTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use Rector\BetterPhpDocParser\PhpDocInfo\PhpDocInfoFactory;
use Rector\BetterPhpDocParser\ValueObject\Type\FullyQualifiedIdentifierTypeNode;
use Rector\Comments\NodeDocBlock\DocBlockUpdater;
use Rector\Rector\AbstractRector;
use Rector\StaticTypeMapper\StaticTypeMapper;
use Rector\StaticTypeMapper\ValueObject\Type\AliasedObjectType;
use Rector\StaticTypeMapper\ValueObject\Type\FullyQualifiedObjectType;
use Rector\StaticTypeMapper\ValueObject\Type\ShortenedObjectType;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;
use Zenstruck\Foundry\FactoryCollection;
use Zenstruck\Foundry\Persistence\Proxy;

final class AddProxyToFactoryCollectionTypeInPhpDoc extends AbstractRector
{
    private PersistenceResolver $persistenceResolver;

    public function __construct(
        private PhpDocInfoFactory $phpDocInfoFactory,
        private StaticTypeMapper $staticTypeMapper,
        private DocBlockUpdater $docBlockUpdater,
        ?PersistenceResolver $persistenceResolver,
    ) {
        $this->persistenceResolver = $persistenceResolver ?? new PersistenceResolver();
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Add "Proxy" generic type to FactoryCollection in docblock if missing. This will only affect persistent objects using proxy.',
            [
                new CodeSample(
                    <<<'CODE_SAMPLE'
                        /**
                         * @param FactoryCollection<SomeObject> $factoryCollection
                         */
                        public function foo(FactoryCollection $factoryCollection);
                        CODE_SAMPLE,
                    <<<'CODE_SAMPLE'
                        /**
                         * @param FactoryCollection<\Zenstruck\Foundry\Persistence\Proxy<SomeObject>> $factoryCollection
                         */
                        public function foo(FactoryCollection $factoryCollection);
                        CODE_SAMPLE
                ),
            ]
        );
    }

    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [Node\Stmt\ClassMethod::class];
    }

    public function refactor(Node $node): ?Node
    {
        return match ($node::class) {
            Node\Stmt\ClassMethod::class => $this->changeParamFactoryCollection($node) ? $node : null,
            default => null,
        };
    }

    private function changeParamFactoryCollection(Node\Stmt\ClassMethod $node): bool
    {
        $phpDocInfo = $this->phpDocInfoFactory->createFromNode($node);

        if (!$phpDocInfo) {
            return false;
        }

        $hasChanged = false;

        $phpDocNode = $phpDocInfo->getPhpDocNode();
        foreach ($phpDocNode->children as $phpDocChildNode) {
            // assert we have a param with format `@param FactoryCollection<Something>`
            if (!$phpDocChildNode instanceof PhpDocTagNode
                || '@param' !== $phpDocChildNode->name
                || !$phpDocChildNode->value instanceof ParamTagValueNode
                || !($genericTypeNode = $phpDocChildNode->value->type) instanceof GenericTypeNode
                || !\str_contains((string) $phpDocChildNode, 'FactoryCollection<')
                || 1 !== \count($genericTypeNode->genericTypes)
            ) {
                continue;
            }

            // assert we really have a `FactoryCollection` from foundry
            if (FactoryCollection::class !== $this->getFullyQualifiedClassName($genericTypeNode->type, $node)) {
                continue;
            }

            // handle case FactoryCollection<Proxy<NotPersisted>>
            if (($factoryGenericType = $genericTypeNode->genericTypes[0]) instanceof GenericTypeNode) {
                if (1 === \count($factoryGenericType->genericTypes)) {
                    $proxy = $this->getFullyQualifiedClassName($factoryGenericType->type, $node);
                    $proxyTargetClassName = $this->getFullyQualifiedClassName($factoryGenericType->genericTypes[0], $node);

                    if (!$proxy || !$proxyTargetClassName) {
                        continue;
                    }

                    if (\is_a($proxy, Proxy::class, allow_string: true) && !$this->persistenceResolver->shouldUseProxyFactory($proxyTargetClassName)) {
                        $hasChanged = true;
                        $phpDocChildNode->value->type->genericTypes = [new FullyQualifiedIdentifierTypeNode($proxyTargetClassName)];
                    }
                }

                continue;
            }

            // assert generic type will effectively come from PersistentProxyObjectFactory
            $targetClassName = $this->getFullyQualifiedClassName($genericTypeNode->genericTypes[0], $node);
            if (!$targetClassName || !$this->persistenceResolver->shouldUseProxyFactory($targetClassName)) {
                continue;
            }

            $hasChanged = true;
            $phpDocChildNode->value->type->genericTypes = [new GenericTypeNode(new FullyQualifiedIdentifierTypeNode(Proxy::class), $genericTypeNode->genericTypes)];
        }

        if ($hasChanged) {
            $this->docBlockUpdater->updateRefactoredNodeWithPhpDocInfo($node);
        }

        return $hasChanged;
    }

    /**
     * @return class-string|null
     */
    private function getFullyQualifiedClassName(TypeNode $typeNode, Node $node): ?string
    {
        $type = $this->staticTypeMapper->mapPHPStanPhpDocTypeNodeToPHPStanType($typeNode, $node);

        return match ($type::class) { // @phpstan-ignore-line
            FullyQualifiedObjectType::class => $type->getClassName(),
            ShortenedObjectType::class, AliasedObjectType::class => $type->getFullyQualifiedName(),
            default => null,
        };
    }
}
