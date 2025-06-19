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
use PhpParser\Node\FunctionLike;
use PHPStan\Type\ClosureType;
use PHPStan\Type\ErrorType;
use PHPStan\Type\MixedType;
use PHPStan\Type\ObjectType;
use Rector\Rector\AbstractRector;
use Zenstruck\Foundry\Persistence\Proxy;

/**
 * Remove useless array_map() which calls ->_real() on proxies list.
 */
final class RemoveUnproxifyArrayMapRector extends AbstractRector
{
    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [Node\Expr\FuncCall::class];
    }

    /**
     * @param Node\Expr\FuncCall $node
     */
    public function refactor(Node $node): ?Node
    {
        if ('array_map' !== $this->getName($node->name)) {
            return null;
        }

        if (2 !== \count($node->args)) {
            return null;
        }

        // if the callable looks like "fn(Proxy $p) => $p->object()"
        if (!$this->isCallableUnproxify($node)) {
            return null;
        }

        // then replace the array_map by it's array param
        return $node->getArgs()[1]->value;
    }

    private function isCallableUnproxify(Node\Expr\FuncCall $node): bool
    {
        $callable = $node->getArgs()[0]->value;

        if (!$this->getType($callable) instanceof ClosureType) {
            return false; // first argument can be any type of callable, but let's only handle closures
        }

        if (!$callable instanceof FunctionLike) {
            return false; // at this point this shoudl not happend
        }

        if (1 !== \count($callable->getParams())) {
            return false; // let's only handle callables with one param
        }

        $paramType = $this->getType($callable->getParams()[0]);

        if (!$paramType->accepts(new ObjectType(Proxy::class), true)->yes()
            || $paramType instanceof MixedType
        ) {
            return false;
        }

        // assert the body of the callable is a single ->_real() call on its unique param
        return 1 === \count($callable->getStmts() ?? [])
            && ($return = $callable->getStmts()[0]) instanceof Node\Stmt\Return_
            && (($methodCall = $return->expr) instanceof Node\Expr\MethodCall || $methodCall instanceof Node\Expr\NullsafeMethodCall)
            && $this->getName($methodCall->var) === $this->getName($callable->getParams()[0]->var)
            && $this->getName($methodCall->name) === '_real'
            && 0 === \count($methodCall->args)
        ;
    }
}
