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

namespace Zenstruck\Foundry\Utils\Rector\RemoveFunctionCall;

use PhpParser\Node;
use PhpParser\NodeVisitor;
use Rector\Contract\Rector\ConfigurableRectorInterface;
use Rector\Rector\AbstractRector;

final class RemoveFunctionCallRector extends AbstractRector implements ConfigurableRectorInterface
{
    /** @var RemoveFunctionCall[] */
    private array $removeFunctionCalls = [];

    /** @return array<class-string<Node>> */
    public function getNodeTypes() : array
    {
        return [Node\Stmt\Expression::class];
    }

    /** @param Node\Stmt\Expression $node */
    public function refactor(Node $node) : int|null
    {
        foreach ($this->removeFunctionCalls as $removeFunctionCall) {
            if ($node->expr instanceof Node\Expr\FuncCall && !$node->expr->isFirstClassCallable() && $this->isName($node->expr, $removeFunctionCall->functionName)) {
                return NodeVisitor::REMOVE_NODE;
            }

            $assign = $node->expr;

            if (!$assign instanceof Node\Expr\Assign) {
                return null;
            }

            if ($assign->expr instanceof Node\Expr\FuncCall && !$assign->expr->isFirstClassCallable() && $this->isName($assign->expr, $removeFunctionCall->functionName)) {
                if (!isset($assign->expr->args[0]) || $assign->expr->args[0] instanceof Node\VariadicPlaceholder) {
                    return null;
                }

                if ($assign->var instanceof Node\Expr\Variable
                    && $assign->expr->args[0]->value instanceof Node\Expr\Variable
                    && $assign->var->name === $assign->expr->args[0]->value->name) {
                    return NodeVisitor::REMOVE_NODE;
                }

                $assign->expr = $assign->expr->args[0]->value;
            }
        }

        return null;
    }

    /**
     * @param mixed[] $configuration
     */
    public function configure(array $configuration) : void
    {
        foreach ($configuration as $configItem) {
            if (!$configItem instanceof RemoveFunctionCall) {
                throw new \InvalidArgumentException(sprintf('Expected instance of "%s", got "%s".', RemoveFunctionCall::class, get_debug_type($configItem)));
            }
        }
        $this->removeFunctionCalls = $configuration;
    }
}
