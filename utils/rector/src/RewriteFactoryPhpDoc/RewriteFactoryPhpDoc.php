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

namespace Zenstruck\Foundry\Utils\Rector\RewriteFactoryPhpDoc;

use PhpParser\Node;
use PHPStan\PhpDocParser\Ast\PhpDoc\MethodTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTextNode;
use PHPStan\Type\ObjectType;
use Rector\BetterPhpDocParser\PhpDocInfo\PhpDocInfoFactory;
use Rector\BetterPhpDocParser\PhpDocManipulator\PhpDocTagRemover;
use Rector\Comments\NodeDocBlock\DocBlockUpdater;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\ObjectFactory;
use Zenstruck\Foundry\Utils\Rector\PersistenceResolver;

final class RewriteFactoryPhpDoc extends AbstractRector
{
    public function __construct(
        private PhpDocTagRemover $phpDocTagRemover,
        private PhpDocInfoFactory $phpDocInfoFactory,
        private DocBlockUpdater $docBlockUpdater,
        private PersistenceResolver $persistenceResolver,
    ) {
    }

    /** prevents infinite loop */
    private array $traversedClasses = [];

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Remove all @method and @phpstan-method tags for ObjectFactory. Completely rewrite them for PersistentProxyObjectFactory',
            [
            ]
        );
    }

    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [Node\Stmt\Class_::class];
    }

    /**
     * @param Node\Stmt\Class_ $node
     */
    public function refactor(Node $node): Node\Stmt\Class_|null
    {
        if (!$this->isObjectType($node, new ObjectType(ModelFactory::class))) {
            return null;
        }

        if (isset($this->traversedClasses[$this->getName($node)])) {
            return null;
        }

        $this->traversedClasses[$this->getName($node)] = true;

        $phpDocInfo = $this->phpDocInfoFactory->createFromNode($node);

        if (!$phpDocInfo) {
            return null;
        }

        $foundryMethodPhpDocNodes = $this->selectFoundryMethodsPhpDocNodes($phpDocInfo->getPhpDocNode());

        if (!$foundryMethodPhpDocNodes) {
            return null;
        }

        // remove all @method-ish php doc
        foreach ($foundryMethodPhpDocNodes as $foundryMethodPhpDocNode) {
            $this->phpDocTagRemover->removeTagValueFromNode($phpDocInfo, $foundryMethodPhpDocNode);
        }

        /** @var class-string<ObjectFactory> $factoryClass */
        $factoryClass = $this->getName($node);
        $targetClassName = $this->persistenceResolver->targetClass($factoryClass);

        // add rewrite them all if we have a proxy persistent factory
        if (!$this->persistenceResolver->shouldTransformFactoryIntoObjectFactory($factoryClass)) {
            foreach (array_keys(MethodTagFactory::METHODS) as $methodName) {
                $phpDocInfo->addPhpDocTagNode(MethodTagFactory::methodTag($methodName, $targetClassName, preciseType: false));
            }

            $phpDocInfo->addPhpDocTagNode(
                MethodTagFactory::repositoryMethodTag(
                    targetClassName: $targetClassName,
                    repositoryClassName: $this->persistenceResolver->geRepositoryClass($targetClassName)
                )
            );

            $phpDocInfo->addPhpDocTagNode(new PhpDocTextNode(''));

            foreach (array_keys(MethodTagFactory::METHODS) as $methodName) {
                $phpDocInfo->addPhpDocTagNode(MethodTagFactory::methodTag($methodName, $targetClassName, preciseType: true));
            }
        }

        $this->docBlockUpdater->updateRefactoredNodeWithPhpDocInfo($node);

        return $node;
    }

    /**
     * @return MethodTagValueNode[]
     */
    private function selectFoundryMethodsPhpDocNodes(PhpDocNode $phpDocNode): array
    {
        /** @var MethodTagValueNode[] $methodPhpDocNodes  */
        $methodPhpDocNodes = [
            ...$phpDocNode->getMethodTagValues(),
            ...$phpDocNode->getMethodTagValues('@phpstan-method'),
            ...$phpDocNode->getMethodTagValues('@psalm-method'),
        ];

        return array_filter(
            $methodPhpDocNodes,
            static fn(MethodTagValueNode $n) => \in_array($n->methodName, MethodTagFactory::allMethodNames(), true)
        );
    }
}
