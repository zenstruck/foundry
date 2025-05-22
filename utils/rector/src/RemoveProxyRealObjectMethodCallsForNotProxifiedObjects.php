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
use PHPStan\Analyser\MutatingScope;
use PHPStan\Reflection\MissingMethodFromReflectionException;
use PHPStan\Type\ErrorType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\UnionType;
use Rector\PHPStan\ScopeFetcher;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;
use Zenstruck\Foundry\Persistence\Proxy;

final class RemoveProxyRealObjectMethodCallsForNotProxifiedObjects extends AbstractRector
{
    private PersistenceResolver $persistenceResolver;

    public function __construct(
        ?PersistenceResolver $persistenceResolver,
        private readonly ScopeFetcher $scopeFetcher,
    ) {
        $this->persistenceResolver = $persistenceResolver ?? new PersistenceResolver();
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Remove `->object()`/`->_real()` calls on objects created by `ObjectFactory`.',
            [
                new CodeSample(
                    <<<'CODE_SAMPLE'
                        SomeObjectFactory::new()->create()->object();
                        SomeObjectFactory::new()->create()->_real();
                        CODE_SAMPLE,
                    <<<'CODE_SAMPLE'
                        SomeObjectFactory::new()->create();
                        SomeObjectFactory::new()->create();
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
        return [Node\Expr\MethodCall::class, Node\Expr\NullsafeMethodCall::class];
    }

    /**
     * @param Node\Expr\MethodCall|Node\Expr\NullsafeMethodCall $node
     */
    public function refactor(Node $node): ?Node
    {
        if (!\in_array($this->getName($node->name), ['object', '_real'], true)) {
            return null;
        }

        if ($node->var instanceof Node\Expr\FuncCall) {
            $name = $node->var->name->getAttribute('namespacedName') ?? $this->getName($node->var->name);
            if (\str_starts_with($name, '\\')) {
                $name = \mb_substr($name, 1);
            }

            if (\in_array($name, ['Zenstruck\Foundry\create', 'Zenstruck\Foundry\instantiate', 'Zenstruck\Foundry\Persistence\proxy'])) {
                return null;
            }
        }

        /**
         * If "object()" or "_real()" is called on an object which is a proxy,
         * we should check if this object will use `ObjectFactory` as factory.
         * If it does, we must remove the call.
         */
        if ($this->isProxyOfFutureObjectFactory($node->var)) {
            return $node->var;
        }

        /**
         * If "object()" or "_real()" is called on an object which is not a proxy,
         * we MAY have already changed the base factory class.
         * Then, if the method does not exist on the object's class, it is very likely we can safely remove the method call.
         */
        if ($this->isRegularObjectWithoutGivenMethod($node)) {
            return $node->var;
        }

        return null;
    }

    /**
     * We only consider two cases:
     *    - proxy is nullable (ie: UnionType with a NullType)
     *    - proxy is a GenericObjectType
     *
     * Complex cases are not handled here.
     */
    private function isProxyOfFutureObjectFactory(Node\Expr $var): bool
    {
        // not a proxy
        if (!$this->isObjectType($var, new ObjectType(Proxy::class)) && !$this->isObjectType(
            $var,
            new ObjectType(\Zenstruck\Foundry\Proxy::class)
        )) {
            return false;
        }

        /** @var MutatingScope $mutatingScope */
        $mutatingScope = $var->getAttribute('scope');

        $type = $mutatingScope->getType($var);

        // Proxy is nullable: we extract the proxy type
        if ($type instanceof UnionType) {
            $types = $type->getTypes();

            if (\count($types) > 2) {
                return false;
            }

            if ($types[0]->isNull()) { // @phpstan-ignore if.alwaysTrue
                $type = $types[1];
            } elseif ($types[1]->isNull()) { // @phpstan-ignore elseif.alwaysTrue
                $type = $types[0];
            } else {
                return false;
            }
        }

        $templateType = $type->getTemplateType(Proxy::class, 'TProxiedObject');

        return !$templateType instanceof ErrorType
            && $templateType->isObject()->yes()
            && count($templateType->getObjectClassNames()) === 1
            && !$this->persistenceResolver->shouldUseProxyFactory($templateType->getObjectClassNames()[0]); // @phpstan-ignore argument.type
    }

    private function isRegularObjectWithoutGivenMethod(Node\Expr\MethodCall|Node\Expr\NullsafeMethodCall $node): bool
    {
        $type = $this->getType($node->var);

        if (!$type->isObject()->yes()) {
            return false;
        }

        if (count($classReflections = $type->getObjectClassReflections()) !== 1) {
            return false;
        }

        try {
            $classReflections[0]->getMethod($this->getName($node->name) ?? '', $this->scopeFetcher->fetch($node));

            return false;
        } catch (MissingMethodFromReflectionException) {
            return true;
        }
    }
}
