<?php

declare(strict_types=1);

namespace Zenstruck\Foundry\Utils\Rector;

use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PHPStan\Analyser\MutatingScope;
use PHPStan\PhpDocParser\Ast\PhpDoc\ExtendsTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\MethodTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTagNode;
use PHPStan\PhpDocParser\Ast\Type\GenericTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\UnionTypeNode;
use PHPStan\Type\ObjectType;
use Rector\BetterPhpDocParser\PhpDocInfo\PhpDocInfoFactory;
use Rector\BetterPhpDocParser\PhpDocManipulator\PhpDocTagRemover;
use Rector\Comments\NodeDocBlock\DocBlockUpdater;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\ObjectFactory;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;
use Zenstruck\Foundry\Persistence\RepositoryDecorator;

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
        return [Node\Stmt\Class_::class];
    }

    /**
     * @param Node\Stmt\Class_ $node
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

    private function changeBaseClass(Node\Stmt\Class_ $node): Node\Stmt\Class_|null
    {
        /** @var MutatingScope $mutatingScope */
        $mutatingScope = $node->getAttribute('scope');
        $classReflection = $mutatingScope->getClassReflection();

        if (
            !str_starts_with($classReflection->getParentClass()->getName(), 'Zenstruck\Foundry')
            || str_starts_with($classReflection->getParentClass()->getName(), 'Zenstruck\Foundry\Utils\Rector')
        ) {
            $newFactoryClass = $classReflection->getParentClass()->getName();
        } elseif ($this->persistenceResolver->shouldTransformFactoryIntoObjectFactory($this->getName($node))) {
            $newFactoryClass = '\\' . ObjectFactory::class;
            $node->extends = new Node\Name($newFactoryClass);
        } else {
            $newFactoryClass = '\\' . PersistentProxyObjectFactory::class;
            $node->extends = new Node\Name($newFactoryClass);
        }

        $this->updatePhpDoc($node, $newFactoryClass);
        $this->docBlockUpdater->updateRefactoredNodeWithPhpDocInfo($node);

        return $node;
    }

    /**
     * - updates `@extends` annotation
     * - removes `@method` annotations when not using proxies anymore
     * - change `@method` annotations for method `repository()` otherwise it breaks type system
     */
    private function updatePhpDoc(Node\Stmt\Class_ $node, string $newFactoryClass): void
    {
        $phpDocInfo = $this->phpDocInfoFactory->createFromNode($node);

        if (!$phpDocInfo) {
            return;
        }

        $phpDocNode = $phpDocInfo->getPhpDocNode();
        foreach ($phpDocNode->children as $phpDocChildNode) {
            if (!$phpDocChildNode instanceof PhpDocTagNode
                || $phpDocChildNode->name !== '@extends'
                || !$phpDocChildNode->value instanceof ExtendsTagValueNode
                || count($phpDocChildNode->value->type->genericTypes) !== 1
            ) {
                continue;
            }

            $phpDocChildNode->value->type->type = new IdentifierTypeNode($newFactoryClass);
            break;
        }

        if ($newFactoryClass === '\\' . ObjectFactory::class) {
            $this->phpDocTagRemover->removeByName($phpDocInfo, 'method');
            $this->phpDocTagRemover->removeByName($phpDocInfo, 'phpstan-method');
            $this->phpDocTagRemover->removeByName($phpDocInfo, 'psalm-method');
        } else {
            /** @var MethodTagValueNode[] $methodNodes */
            $methodNodes = [
                ...$phpDocNode->getMethodTagValues(),
                ...$phpDocNode->getMethodTagValues('@phpstan-method'),
                ...$phpDocNode->getMethodTagValues('@psalm-method'),
            ];

            foreach ($methodNodes as $methodNode) {
                if (!str_contains((string) $methodNode->returnType, 'RepositoryProxy')) {
                    continue;
                }

                match(true){
                    $methodNode->returnType instanceof IdentifierTypeNode => $methodNode->returnType = new IdentifierTypeNode('\\'.RepositoryDecorator::class),
                    $methodNode->returnType instanceof GenericTypeNode => $methodNode->returnType->type = new IdentifierTypeNode('\\'.RepositoryDecorator::class),
                    $methodNode->returnType instanceof UnionTypeNode => (static function() use ($methodNode){
                        foreach ($methodNode->returnType->types as $key => $type) {
                            if ($type instanceof IdentifierTypeNode && str_contains($type->name, 'RepositoryProxy')) {
                                // does not work by just updating node's name
                                unset($methodNode->returnType->types[$key]);
                                $methodNode->returnType->types[] = new IdentifierTypeNode('\\'.RepositoryDecorator::class);
                            }
                        }
                    })(),
                    default => null
                };
            }
        }
    }

    private function changeFactoryMethods(Node\Stmt\Class_ $node): void
    {
        foreach ($node->getMethods() as $method) {
            $methodName = $this->getName($method);

            if ($methodName == 'getDefaults') {
                $method->name = new Node\Identifier('defaults');
            }

            if ($methodName == 'getClass') {
                $method->name = new Node\Identifier('class');
                $method->flags = Class_::MODIFIER_PUBLIC | Class_::MODIFIER_STATIC;
            }

            if ($methodName == 'initialize') {
                $method->returnType = new Node\Identifier('static');
            }
        }
    }
}
