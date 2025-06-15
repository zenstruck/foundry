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
use Rector\Rector\AbstractRector;

final class RemoveWithoutAutorefreshCallRector extends AbstractRector
{
    /** @return array<class-string<Node>> */
    public function getNodeTypes() : array
    {
        return [Node\Stmt\Expression::class];
    }

    /** @param Node\Stmt\Expression $node */
    public function refactor(Node $node) : array|Node|null
    {
        $method = $node->expr;

        if (!$method instanceof Node\Expr\MethodCall
            || $method->isFirstClassCallable()
            || !$method->var instanceof Node\Expr\Variable
            || !$this->isName($method->name, '_withoutAutoRefresh')
            || !isset($method->args[0])
            || $method->args[0] instanceof Node\VariadicPlaceholder
        ) {
            return null;
        }

        $arg = $method->args[0]->value;

        if ($arg instanceof Node\Expr\Closure) {
            return $arg->stmts;
        }

        if ($arg instanceof Node\Expr\FuncCall && $arg->isFirstClassCallable()) {
            return new Node\Stmt\Expression(
                new Node\Expr\FuncCall(
                    $arg->name,
                    [new Node\Arg($method->var)]
                )
            );
        }

        if ($arg instanceof Node\Expr\MethodCall && $arg->isFirstClassCallable()) {
            return new Node\Stmt\Expression(
                new Node\Expr\MethodCall(
                    $arg->var,
                    $arg->name,
                    [new Node\Arg($method->var)]
                )
            );
        }

        return null;
    }
}
