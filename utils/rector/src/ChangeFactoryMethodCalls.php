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
    ) {
    }

    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [MethodCall::class, StaticCall::class];
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
        return match ($node::class) {
            MethodCall::class => $this->changeMethodCall($node),
            StaticCall::class => $this->changeStaticCall($node),
            default => null,
        };
    }

    public function changeMethodCall(MethodCall $node): Node|int|null
    {
        if (!$this->isObjectType($node->var, new ObjectType(Factory::class))) {
            return null;
        }

        if (\in_array($this->getName($node->name), ['addState', 'withAttributes'], true)) {
            $node->name = new Node\Identifier('with');

            return $node;
        }

        if ('withoutPersisting' === $this->getName($node->name)) {
            $type = $this->getType($node->var);
            $classes = $type->getObjectClassNames();
            if (1 === \count($classes) && $this->persistenceResolver->shouldTransformFactoryIntoObjectFactory($classes[0])) { // @phpstan-ignore-line
                return $node->var;
            }

            return null;
        }

        return null;
    }

    private function changeStaticCall(StaticCall $node): ?Node
    {
        if (!$this->isObjectType($node->class, new ObjectType(Factory::class))) {
            return null;
        }

        if ('getDefaults' === $this->getName($node->name)) {
            $node->name = new Node\Identifier('defaults');

            return $node;
        }

        return null;
    }
}
