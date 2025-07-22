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

namespace Zenstruck\Foundry\Utils\Rector\RemoveMethodCall;

use PhpParser\Node;
use Rector\Contract\Rector\ConfigurableRectorInterface;
use Rector\Rector\AbstractRector;

final class RemoveMethodCallRector extends AbstractRector implements ConfigurableRectorInterface
{
    /** @var RemoveMethodCall[] */
    private array $removeMethodCalls = [];

    /** @return array<class-string<Node>> */
    public function getNodeTypes() : array
    {
        return [Node\Expr\MethodCall::class, Node\Expr\NullsafeMethodCall::class, Node\Stmt\Expression::class];
    }

    /** @param Node\Expr\MethodCall|Node\Expr\NullsafeMethodCall|Node\Stmt\Expression $node */
    public function refactor(Node $node) : Node|int|null
    {
        foreach ($this->removeMethodCalls as $removeMethodCall) {
            if ($node instanceof Node\Stmt\Expression) {
                // remove calls like "$a->method();"
                if ($this->isOnlyMethodCall($node->expr, $removeMethodCall)
                ) {
                    return \PhpParser\NodeVisitor::REMOVE_NODE;
                }

                // remove calls like "$a = $a->method();"
                if (
                    $node->expr instanceof Node\Expr\Assign
                    && $node->expr->var instanceof Node\Expr\Variable
                    && $this->isOnlyMethodCall($node->expr->expr, $removeMethodCall)
                    && $node->expr->expr->var instanceof Node\Expr\Variable
                    && $node->expr->var->name === $node->expr->expr->var->name
                ) {
                    return \PhpParser\NodeVisitor::REMOVE_NODE;
                }

                continue;
            }


            if (!$this->isName($node->name, $removeMethodCall->methodName)) {
                continue;
            }

            return $node->var;
        }

        return null;
    }

    /**
     * @param mixed[] $configuration
     */
    public function configure(array $configuration) : void
    {
        foreach ($configuration as $configItem) {
            if (!$configItem instanceof RemoveMethodCall) {
                throw new \InvalidArgumentException(sprintf('Expected instance of "%s", got "%s".', RemoveMethodCall::class, get_debug_type($configItem)));
            }
        }

        $this->removeMethodCalls = $configuration;
    }

    /**
     * @phpstan-assert-if-true Node\Expr\MethodCall|Node\Expr\NullsafeMethodCall $expr
     */
    private function isOnlyMethodCall(Node\Expr $expr, RemoveMethodCall $removeMethodCall): bool
    {
        return ($expr instanceof Node\Expr\MethodCall || $expr instanceof Node\Expr\NullsafeMethodCall)
            && $this->isName($expr->name, $removeMethodCall->methodName)
            && $expr->var instanceof Node\Expr\Variable;
    }
}
