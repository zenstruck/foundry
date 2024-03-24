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
use PhpParser\Node\Stmt\Class_;
use PHPStan\Analyser\MutatingScope;
use PHPStan\PhpDocParser\Ast\PhpDoc\ExtendsTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTagNode;
use PHPStan\PhpDocParser\Ast\Type\GenericTypeNode;
use PHPStan\Type\ObjectType;
use Rector\BetterPhpDocParser\PhpDocInfo\PhpDocInfoFactory;
use Rector\BetterPhpDocParser\PhpDocManipulator\PhpDocTagRemover;
use Rector\BetterPhpDocParser\ValueObject\Type\FullyQualifiedIdentifierTypeNode;
use Rector\Comments\NodeDocBlock\DocBlockUpdater;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\ObjectFactory;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

final class ChangeFactoryBaseClass extends AbstractRector
{
    private array $traversedClasses = [];

    public function __construct(
        private PhpDocTagRemover $phpDocTagRemover,
        private PhpDocInfoFactory $phpDocInfoFactory,
        private DocBlockUpdater $docBlockUpdater,
        private PersistenceResolver $persistenceResolver,
    ) {
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            <<<DESCRIPTION
                Changes legacy ModelFactory into new factories. This implies few things:
                  - changes the base class:
                      - `ObjectFactory` will be chosen if target class is not an entity (ORM) or a document (ODM)
                      - `PersistentProxyObjectFactory` will be chosen otherwise (this rector currently doesn't use `PersistentProxyObjectFactory`)
                  - migrates `getDefaults()`, `class()` and `initialize()` methods to new name/prototype
                  - add or updates `@extends` php doc tag
                DESCRIPTION,
            [
                new CodeSample(
                    <<<'CODE_SAMPLE'
                        /**
                         * @extends ModelFactory<DummyObject>
                         */
                        final class DummyObjectFactory extends ModelFactory
                        {
                            protected function getDefaults(): array
                            {
                                return [];
                            }

                            protected function initialize(): self
                            {
                                return $this;
                            }

                            protected static function getClass(): string
                            {
                                return DummyObject::class;
                            }
                        }
                        CODE_SAMPLE,
                    <<<'CODE_SAMPLE'
                        /**
                         * @extends \Zenstruck\Foundry\ObjectFactory<DummyObject>
                         */
                        final class DummyObjectFactory extends \Zenstruck\Foundry\ObjectFactory
                        {
                            protected function defaults(): array
                            {
                                return [];
                            }

                            protected function initialize(): static
                            {
                                return $this;
                            }

                            public static function class(): string
                            {
                                return DummyObject::class;
                            }
                        }
                        CODE_SAMPLE
                ),
                new CodeSample(
                    <<<'CODE_SAMPLE'
                        /**
                         * @extends ModelFactory<DummyPersistentObject>
                         */
                        final class DummyPersistentProxyFactory extends ModelFactory
                        {
                            protected function getDefaults(): array
                            {
                                return [];
                            }

                            protected function initialize(): self
                            {
                                return $this;
                            }

                            protected static function getClass(): string
                            {
                                return DummyPersistentObject::class;
                            }
                        }
                        CODE_SAMPLE,
                    <<<'CODE_SAMPLE'
                        /**
                         * @extends \Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory<DummyPersistentObject>
                         */
                        final class DummyPersistentProxyFactory extends \Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory
                        {
                            protected function defaults(): array
                            {
                                return [];
                            }

                            protected function initialize(): static
                            {
                                return $this;
                            }

                            public static function class(): string
                            {
                                return DummyPersistentObject::class;
                            }
                        }
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
        return [Class_::class];
    }

    /**
     * @param Class_ $node
     */
    public function refactor(Node $node): ?Node
    {
        if (!$this->isObjectType($node, new ObjectType(ModelFactory::class))) {
            return null;
        }

        if (isset($this->traversedClasses[$this->getName($node)])) {
            return null;
        }

        $this->traversedClasses[$this->getName($node)] = true;

        $baseClassChanged = $this->changeBaseClass($node);
        $methodChanged = $this->changeFactoryMethods($node);
        
        return $methodChanged || $baseClassChanged ? $node : null;
    }

    private function changeBaseClass(Class_ $node): bool
    {
        /** @var MutatingScope $mutatingScope */
        $mutatingScope = $node->getAttribute('scope');
        $parentFactoryReflection = $mutatingScope->getClassReflection()?->getParentClass();

        if (!$parentFactoryReflection) {
            return false;
        }

        if ($parentFactoryReflection->getName() !== ModelFactory::class) {
            return false;
        }
        
        if ($this->persistenceResolver->shouldTransformFactoryIntoObjectFactory($this->getName($node))) { // @phpstan-ignore-line
            $newFactoryClass = ObjectFactory::class;
        } else {
            $newFactoryClass = PersistentProxyObjectFactory::class;
        }

        $node->extends = new Node\Name\FullyQualified($newFactoryClass);
        $this->updateExtendsPhpDoc($node, $newFactoryClass);

        return true;
    }

    private function updateExtendsPhpDoc(Class_ $node, string $newFactoryClass): void
    {
        $phpDocInfo = $this->phpDocInfoFactory->createFromNodeOrEmpty($node);

        $phpDocNode = $phpDocInfo->getPhpDocNode();

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
                        new FullyQualifiedIdentifierTypeNode($newFactoryClass),
                        [
                            new FullyQualifiedIdentifierTypeNode(
                                $this->persistenceResolver->targetClass($this->getName($node) ?? '') // @phpstan-ignore-line
                            ),
                        ]
                    ),
                    description: ''
                )
            )
        );

        $this->docBlockUpdater->updateRefactoredNodeWithPhpDocInfo($node);
    }

    private function changeFactoryMethods(Class_ $node): bool
    {
        $modified  = false;

        foreach ($node->getMethods() as $method) {
            $methodName = $this->getName($method);

            if ('getDefaults' == $methodName) {
                $method->name = new Node\Identifier('defaults');
                $modified = true;
            }

            if ('getClass' == $methodName) {
                $method->name = new Node\Identifier('class');
                $method->flags = Class_::MODIFIER_PUBLIC | Class_::MODIFIER_STATIC;
                $modified = true;
            }

            if ('initialize' == $methodName) {
                $method->returnType = new Node\Identifier('static');
                $modified = true;
            }
        }

        return $modified;
    }
}
