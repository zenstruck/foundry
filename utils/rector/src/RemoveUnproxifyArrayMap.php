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
use PHPStan\Type\ArrayType;
use PHPStan\Type\ClosureType;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\TypeWithClassName;
use Rector\Rector\AbstractRector;
use Rector\StaticTypeMapper\ValueObject\Type\FullyQualifiedObjectType;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;
use Zenstruck\Foundry\Persistence\Proxy;

final class RemoveUnproxifyArrayMap extends AbstractRector
{
    public function __construct(
        private PersistenceResolver $persistenceResolver,
    ) {
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Remove useless array_map() which calls ->object() on proxies list.',
            [
                new CodeSample(
                    <<<'CODE_SAMPLE'
                        array_map(fn(Proxy $proxy) => $proxy->object, ObjectFactory::createMany())
                        CODE_SAMPLE,
                    <<<'CODE_SAMPLE'
                        ObjectFactory::createMany()
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

        // and the param is an array of objects which are NOT proxies
        if (!$this->isArrayMapTargetingNonProxyObject($node)) {
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

        if (!$paramType instanceof TypeWithClassName || !\is_a($paramType->getClassName(), Proxy::class, allow_string: true)) {
            return false; // let's only handle param when it is fully typed as a Proxy
        }

        // assert the body of the callable is a single ->object() / ->_real() call on its unique param
        return 1 === \count($callable->getStmts() ?? [])
            && ($return = $callable->getStmts()[0]) instanceof Node\Stmt\Return_
            && ($methodCall = $return->expr) instanceof Node\Expr\MethodCall
            && $this->getName($methodCall->var) === $this->getName($callable->getParams()[0]->var)
            && \in_array($this->getName($methodCall->name), ['_real', 'object'])
            && 0 === \count($methodCall->args)
        ;
    }

    private function isArrayMapTargetingNonProxyObject(Node\Expr\FuncCall $node): bool
    {
        $array = $this->getType($node->getArgs()[1]->value);

        if (!$array instanceof ArrayType) {
            return false; // this should not happen: second argument of an array_map call IS an array
        }

        $iterableType = $array->getIterableValueType();
        if (!$iterableType instanceof TypeWithClassName) {
            // if it is not a TypeWithClassName, we could be in on of these situations, which we cannot handle
            // - the parameter is badly typed
            // - the iterable type is not an object
            // - we have a more complex type
            return false;
        }

        $className = $iterableType->getClassName();

        if (!\is_a($className, Proxy::class, allow_string: true)) {
            return true; // the objects in array param are NOT a proxy, we should make the replacement
        }

        // otherwise, let's check if we have a Proxy<Object> with Object is not persistable.

        if (!$iterableType instanceof GenericObjectType) {
            return false; // not a fully typed generic, cannot guess if Object is persistable
        }

        return 1 === \count($iterableType->getTypes())
            && ($genericType = $iterableType->getTypes()[0]) instanceof TypeWithClassName
            && !$this->persistenceResolver->shouldUseProxyFactory($genericType->getClassName()); // @phpstan-ignore-line
    }
}
