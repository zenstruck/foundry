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
use PHPStan\PhpDocParser\Ast\PhpDoc\MethodTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\MethodTagValueParameterNode;
use PHPStan\PhpDocParser\Ast\Type\GenericTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\UnionTypeNode;
use PHPStan\Type\ObjectType;
use Rector\BetterPhpDocParser\PhpDocInfo\PhpDocInfoFactory;
use Rector\BetterPhpDocParser\PhpDocManipulator\PhpDocTagRemover;
use Rector\BetterPhpDocParser\ValueObject\Type\BracketsAwareIntersectionTypeNode;
use Rector\BetterPhpDocParser\ValueObject\Type\BracketsAwareUnionTypeNode;
use Rector\BetterPhpDocParser\ValueObject\Type\FullyQualifiedIdentifierTypeNode;
use Rector\BetterPhpDocParser\ValueObject\Type\SpacingAwareArrayTypeNode;
use Rector\Comments\NodeDocBlock\DocBlockUpdater;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\ObjectFactory;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;
use Zenstruck\Foundry\Persistence\ProxyRepositoryDecorator;

final class ChangeFactoryBaseClass extends AbstractRector
{
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
                  - modifies PhpDoc:
                    - updates `@extends` annotation
                    - removes `@method` annotations when using `ObjectFactory`
                    - change `@method` annotations for method `repository()` when using `PersistentProxyObjectFactory`. Otherwise it breaks type system
                DESCRIPTION,
            [
                new CodeSample(
                    <<<'CODE_SAMPLE'
                        /**
                         * @extends ModelFactory<DummyObject>
                         *
                         * @method        DummyObject|Proxy     create(array|callable $attributes = [])
                         * @method static DummyObject|Proxy     createOne(array $attributes = [])
                         * @method static DummyObject[]|Proxy[] createMany(int $number, array|callable $attributes = [])
                         * @method static DummyObject[]|Proxy[] createSequence(iterable|callable $sequence)
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
                         *
                         * @method static RepositoryProxy|EntityRepository repository()
                         * @method static RepositoryProxy repository()
                         * @method        DummyPersistentObject|Proxy create(array|callable $attributes = [])
                         *
                         * @phpstan-method Proxy<DummyPersistentObject> create(array|callable $attributes = [])
                         * @phpstan-method static Proxy<DummyPersistentObject> createOne(array $attributes = [])
                         * @phpstan-method static RepositoryProxy<DummyPersistentObject> repository()
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
                         *
                         * @method static EntityRepository|\Zenstruck\Foundry\Persistence\RepositoryDecorator repository()
                         * @method static \Zenstruck\Foundry\Persistence\RepositoryDecorator repository()
                         * @method        DummyPersistentObject|Proxy create(array|callable $attributes = [])
                         *
                         * @phpstan-method Proxy<DummyPersistentObject> create(array|callable $attributes = [])
                         * @phpstan-method static Proxy<DummyPersistentObject> createOne(array $attributes = [])
                         * @phpstan-method static \Zenstruck\Foundry\Persistence\RepositoryDecorator<DummyPersistentObject> repository()
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

        $this->changeBaseClass($node);
        $this->changeFactoryMethods($node);

        return $node;
    }

    private function changeBaseClass(Class_ $node): ?Class_
    {
        /** @var MutatingScope $mutatingScope */
        $mutatingScope = $node->getAttribute('scope');
        $parentFactoryReflection = $mutatingScope->getClassReflection()?->getParentClass();

        if (!$parentFactoryReflection) {
            return null;
        }

        if (
            !\str_starts_with($parentFactoryReflection->getName(), 'Zenstruck\Foundry')
            || \str_starts_with($parentFactoryReflection->getName(), 'Zenstruck\Foundry\Utils\Rector')
        ) {
            $newFactoryClass = $parentFactoryReflection->getName();
        } elseif ($this->persistenceResolver->shouldTransformFactoryIntoObjectFactory($this->getName($node))) { // @phpstan-ignore-line
            $newFactoryClass = ObjectFactory::class;
            $node->extends = new Node\Name\FullyQualified($newFactoryClass);
        } else {
            $newFactoryClass = PersistentProxyObjectFactory::class;
            $node->extends = new Node\Name\FullyQualified($newFactoryClass);
        }

        $this->updatePhpDoc($node, $newFactoryClass);
        $this->docBlockUpdater->updateRefactoredNodeWithPhpDocInfo($node);

        return $node;
    }

    /**
     * - updates `@extends` annotation
     * - removes `@method` annotations when not using proxies anymore
     * - change `@[phpstan|psalm]-method` annotations and rewrite them.
     */
    private function updatePhpDoc(Class_ $node, string $newFactoryClass): void
    {
        $phpDocInfo = $this->phpDocInfoFactory->createFromNode($node);

        if (!$phpDocInfo) {
            return;
        }

        $phpDocNode = $phpDocInfo->getPhpDocNode();

        $extendsDocNode = $phpDocNode->getExtendsTagValues()[0] ?? null;
        if ($extendsDocNode) {
            $extendsDocNode->type->type = new FullyQualifiedIdentifierTypeNode($newFactoryClass);
        }

        if ($newFactoryClass === ObjectFactory::class) {
            $this->phpDocTagRemover->removeByName($phpDocInfo, 'method');
            $this->phpDocTagRemover->removeByName($phpDocInfo, 'phpstan-method');
            $this->phpDocTagRemover->removeByName($phpDocInfo, 'psalm-method');
        } else {
            $targetClassName = $this->persistenceResolver->targetClass($this->getName($node->namespacedName)); // @phpstan-ignore-line

            /** @var MethodTagValueNode[] $methodNodes */
            $methodNodes = [
                ...$phpDocNode->getMethodTagValues(),
                ...$phpDocNode->getMethodTagValues('@phpstan-method'),
                ...$phpDocNode->getMethodTagValues('@psalm-method'),
            ];

            foreach ($methodNodes as $methodNode) {
                if (\in_array($methodNode->methodName, ['create', 'createOne', 'find', 'findOrCreate', 'first', 'last', 'random', 'randomOrCreate'], true)) {
                    if (\str_contains($methodNode->getAttribute('parent')->name, '-method')) {
                        $methodNode->returnType = new BracketsAwareIntersectionTypeNode(
                            [
                                new FullyQualifiedIdentifierTypeNode($targetClassName),
                                new GenericTypeNode(new IdentifierTypeNode('Proxy'), [new FullyQualifiedIdentifierTypeNode($targetClassName)]),
                            ]
                        );
                    } else {
                        $methodNode->returnType = new BracketsAwareUnionTypeNode(
                            [
                                new FullyQualifiedIdentifierTypeNode($targetClassName),
                                new IdentifierTypeNode('Proxy'),
                            ]
                        );
                    }
                } elseif (\in_array($methodNode->methodName, ['all', 'createMany', 'createSequence', 'findBy', 'randomRange', 'randomSet'], true)) {
                    if (\str_contains($methodNode->getAttribute('parent')->name, '-method')) {
                        $methodNode->returnType = new GenericTypeNode(
                            new IdentifierTypeNode('list'),
                            [
                                new BracketsAwareIntersectionTypeNode(
                                    [
                                        new FullyQualifiedIdentifierTypeNode($targetClassName),
                                        new GenericTypeNode(new IdentifierTypeNode('Proxy'), [new FullyQualifiedIdentifierTypeNode($targetClassName)]),
                                    ]
                                ),
                            ]
                        );
                    } else {
                        $methodNode->returnType = new BracketsAwareUnionTypeNode(
                            [
                                new SpacingAwareArrayTypeNode(new FullyQualifiedIdentifierTypeNode($targetClassName)),
                                new SpacingAwareArrayTypeNode(new IdentifierTypeNode('Proxy')),
                            ]
                        );
                    }
                } elseif ('repository' === $methodNode->methodName) {
                    $methodNode->returnType = new GenericTypeNode(
                        new FullyQualifiedIdentifierTypeNode(ProxyRepositoryDecorator::class),
                        [
                            new FullyQualifiedIdentifierTypeNode($targetClassName),
                            new FullyQualifiedIdentifierTypeNode($this->persistenceResolver->geRepositoryClass($targetClassName)),
                        ]
                    );
                }

                // handle case when @method parameter is UnionType
                // this prevents to render it with brackets, which creates parsing error
                foreach ($methodNode->parameters as $parameter) {
                    if ($parameter instanceof MethodTagValueParameterNode && $parameter->type instanceof UnionTypeNode) {
                        $parameter->type = new BracketsAwareUnionTypeNode($parameter->type->types);
                    }
                }
            }
        }
    }

    private function changeFactoryMethods(Class_ $node): void
    {
        foreach ($node->getMethods() as $method) {
            $methodName = $this->getName($method);

            if ('getDefaults' == $methodName) {
                $method->name = new Node\Identifier('defaults');
            }

            if ('getClass' == $methodName) {
                $method->name = new Node\Identifier('class');
                $method->flags = Class_::MODIFIER_PUBLIC | Class_::MODIFIER_STATIC;
            }

            if ('initialize' == $methodName) {
                $method->returnType = new Node\Identifier('static');
            }
        }
    }
}
