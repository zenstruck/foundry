<?php

declare(strict_types=1);

namespace Zenstruck\Foundry\Utils\Rector;

use PhpParser\Node;
use PhpParser\NodeTraverser;
use PHPStan\Type\ThisType;
use PHPUnit\Framework\TestCase;
use Rector\Rector\AbstractRector;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

final class ChangeDisableEnablePersist extends AbstractRector
{
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Deprecated functions Factories::disablePersist() should either be moved to new persistence function if called in a KernelTestCase or removed otherwise.',
            [
                new CodeSample(
                    <<<'CODE_SAMPLE'
                    class SomeTest extends TestCase
                    {
                        use Factories;

                        protected function setUp(): void
                        {
                            $this->enablePersist();
                            $this->disablePersist();
                        }
                    }
                    CODE_SAMPLE,
                    <<<CODE_SAMPLE
                    class SomeTest extends TestCase
                    {
                        use Factories;

                        protected function setUp(): void
                        {
                        }
                    }
                    CODE_SAMPLE
                ),
                new CodeSample(
                    <<<'CODE_SAMPLE'
                    class SomeTest extends TestCase
                    {
                        use Factories;

                        protected function setUp(): void
                        {
                            $this->enablePersist();
                            $this->disablePersist();
                        }
                    }
                    CODE_SAMPLE,
                    <<<CODE_SAMPLE
                    class SomeTest extends TestCase
                    {
                        use Factories;

                        protected function setUp(): void
                        {
                            \Zenstruck\Foundry\Persistence\enable_persisting();
                            \Zenstruck\Foundry\Persistence\disable_persisting();
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
        return [Node\Stmt\Expression::class];
    }

    /**
     * @param Node\Stmt\Expression $node
     */
    public function refactor(Node $node): Node|int|null
    {
        $expr = $node->expr;

        if (!$expr instanceof Node\Expr\MethodCall) {
            return null;
        }

        if ($this->isCallOnThisInPhpUnitTestCase($expr)) {
            return match ((string)$expr->name) {
                'disablePersist', 'enablePersist' => NodeTraverser::REMOVE_NODE,
                default => null,
            };
        } elseif ($this->isCallOnThisInPhpUnitKernelTestCase($expr)) {
            return match ((string)$expr->name) {
                'disablePersist' => new Node\Stmt\Expression(
                    new Node\Expr\FuncCall(
                        new Node\Name('\Zenstruck\Foundry\Persistence\disable_persisting'), $node->expr->args
                    )
                ),
                'enablePersist' => new Node\Stmt\Expression(
                    new Node\Expr\FuncCall(
                        new Node\Name('\Zenstruck\Foundry\Persistence\enable_persisting'), $node->expr->args
                    )
                ),
                default => null,
            };
        }

        return null;
    }

    private function isCallOnThisInPhpUnitTestCase(Node\Expr\MethodCall $node): bool
    {
        $type = $this->getType($node->var);

        return $type instanceof ThisType
            && is_a($type->getClassName(), TestCase::class, allow_string: true)
            && !is_a($type->getClassName(), KernelTestCase::class, allow_string: true);
    }

    private function isCallOnThisInPhpUnitKernelTestCase(Node\Expr\MethodCall $node): bool
    {
        $type = $this->getType($node->var);

        return $type instanceof ThisType
            && is_a($type->getClassName(), KernelTestCase::class, allow_string: true);
    }
}
