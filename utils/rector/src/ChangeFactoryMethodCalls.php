<?php

declare(strict_types=1);

namespace Zenstruck\Foundry\Utils\Rector;

use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PHPStan\Type\ObjectType;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;
use Zenstruck\Foundry\Factory;

final class ChangeFactoryMethodCalls extends AbstractRector
{
    public function __construct(
        private PersistenceResolver $persistenceResolver,
    )
    {
    }

    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [Node\Expr\MethodCall::class, Node\Expr\StaticCall::class];
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Change old factory methods to the new ones.',
            [
                new CodeSample(
                    <<<'CODE_SAMPLE'
                    protected function someMethod()
                    {
                        DummyObjectFactory::new()->withAttributes(['publish' => true]);
                    }
                    CODE_SAMPLE,
                    <<<'CODE_SAMPLE'
                    protected function someMethod()
                    {
                        DummyObjectFactory::new()->with(['publish' => true]);
                    }
                    CODE_SAMPLE
                ),
                new CodeSample(
                    <<<'CODE_SAMPLE'
                    final class SomeFactory extends ObjectFactory
                    {
                        // mandatory functions...

                        public function published(): static
                        {
                            return $this->addState(['publish' => true]);
                        }
                    }
                    CODE_SAMPLE,
                    <<<'CODE_SAMPLE'
                    final class SomeFactory extends ObjectFactory
                    {
                        // mandatory functions...

                        public function published(): static
                        {
                            return $this->with(['publish' => true]);
                        }
                    }
                    CODE_SAMPLE
                ),
            ]
        );
    }

    public function refactor(Node $node): Node|int|null
    {
        return match($node::class) {
            Node\Expr\MethodCall::class => $this->changeMethodCall($node),
            Node\Expr\StaticCall::class => $this->changeStaticCall($node),
            default => null,
        };
    }

    public function changeMethodCall(MethodCall $node): Node|int|null
    {
        if (!$this->isObjectType($node->var, new ObjectType(Factory::class))) {
            return null;
        }

        if (in_array((string)$node->name, ['addState', 'withAttributes'], true)) {
            $node->name = new Node\Identifier('with');

            return $node;
        }

        if ((string)$node->name === 'withoutPersisting') {
            $type = $this->getType($node->var);
            $classes = $type->getObjectClassNames();
            if (\count($classes) === 1 &&  $this->persistenceResolver->shouldTransformFactoryIntoObjectFactory($classes[0])) {
                return $node->var;
            }

           return null;
        }

        return null;
    }

    private function changeStaticCall(StaticCall $node): Node|null
    {
        if (!$this->isObjectType($node->class, new ObjectType(Factory::class))) {
            return null;
        }

        if ((string)$node->name === 'getDefaults') {
            $node->name = new Node\Identifier('defaults');

            return $node;
        }

        return null;
    }
}
