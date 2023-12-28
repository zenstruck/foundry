<?php

declare(strict_types=1);

namespace Zenstruck\Foundry\Utils\Rector;

use PhpParser\Node;
use PHPStan\PhpDocParser\Ast\PhpDoc\ParamTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTagNode;
use PHPStan\PhpDocParser\Ast\Type\GenericTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\Type\Type;
use PHPStan\Type\TypeWithClassName;
use Rector\BetterPhpDocParser\PhpDocInfo\PhpDocInfoFactory;
use Rector\Comments\NodeDocBlock\DocBlockUpdater;
use Rector\Core\Rector\AbstractRector;
use Rector\StaticTypeMapper\StaticTypeMapper;
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
        PersistenceResolver|null $persistenceResolver,
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
            default => null
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
                || $phpDocChildNode->name !== '@param'
                || !$phpDocChildNode->value instanceof ParamTagValueNode
                || !($genericTypeNode = $phpDocChildNode->value->type) instanceof GenericTypeNode
                || !str_contains((string)$phpDocChildNode, 'FactoryCollection<')
                || count($genericTypeNode->genericTypes) !== 1
                || $genericTypeNode->genericTypes[0] instanceof GenericTypeNode
            ) {
                continue;
            }

            // assert we really have a `FactoryCollection` from foundry
            if ($this->getFullyQualifiedClassName($genericTypeNode->type, $node) !== FactoryCollection::class) {
                continue;
            }

            // assert generic type will effectively come from PersistentProxyObjectFactory
            $targetClassName = $this->getFullyQualifiedClassName($genericTypeNode->genericTypes[0], $node);
            if (!$targetClassName || !$this->persistenceResolver->shouldUseProxyFactory($targetClassName)) {
                continue;
            }

            $hasChanged = true;
            $phpDocChildNode->value->type->genericTypes = [new GenericTypeNode(new IdentifierTypeNode('\\'.Proxy::class), $genericTypeNode->genericTypes)];
        }

        if ($hasChanged) {
            $this->docBlockUpdater->updateRefactoredNodeWithPhpDocInfo($node);
        }

        return $hasChanged;
    }

    /**
     * @return class-string
     */
    private function getFullyQualifiedClassName(TypeNode $typeNode, Node $node): string|null
    {
        $type = $this->staticTypeMapper->mapPHPStanPhpDocTypeNodeToPHPStanType($typeNode, $node);

        return match ($type::class) {
            FullyQualifiedObjectType::class => $type->getClassName(),
            ShortenedObjectType::class => $type->getFullyQualifiedName(),
            default => null,
        };
    }
}
