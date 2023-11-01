<?php

declare(strict_types=1);

namespace Zenstruck\Foundry\Utils\Rector;

use PhpParser\Node;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\NullType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\UnionType;
use Rector\Rector\AbstractRector;
use Rector\StaticTypeMapper\ValueObject\Type\FullyQualifiedObjectType;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;
use Zenstruck\Foundry\Persistence\Proxy;

final class RemoveProxyRealObjectMethodCallsForNotProxifiedObjects extends AbstractRector
{
    private PersistenceResolver $persistenceResolver;

    public function __construct(
        PersistenceResolver|null $persistenceResolver,
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
        if (!in_array((string)$node->name, ['object', '_real'], true)) {
            return null;
        }

        if ($node->var instanceof Node\Expr\FuncCall) {
            $name = $node->var->name->getAttribute('namespacedName') ?? (string)$node->var->name;
            if (str_starts_with($name, '\\')) {
                $name = substr($name, 1);
            }

            if (in_array($name, ['Zenstruck\Foundry\create', 'Zenstruck\Foundry\instantiate', 'Zenstruck\Foundry\Persistence\proxy'])) {
                return null;
            }
        }

        /**
         * If "object()" or "_real()" is called on an object which is a proxy,
         * we should check if this object will use `ObjectFactory` as factory.
         * If it does, we must remove the call
         */
        if ($this->isProxyOfFutureObjectFactory($node->var)) {
            return $node->var;
        }

        /**
         * If "object()" or "_real()" is called on an object which is not a proxy,
         * we MAY have already changed the base factory class.
         * Then, if the method does not exist on the object's class, it is very likely we can safely remove the method call
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

            if (count($types) > 2) {
                return false;
            }

            if ($types[0] instanceof NullType) {
                $type = $types[1];
            } elseif ($types[1] instanceof NullType) {
                $type = $types[0];
            } else {
                return false;
            }
        }

        return $type instanceof GenericObjectType
            && count($types = $type->getTypes()) === 1
            && $types[0] instanceof ObjectType
            && !$this->persistenceResolver->shouldUseProxyFactory($types[0]->getClassName());
    }

    private function isRegularObjectWithoutGivenMethod(Node\Expr\MethodCall|Node\Expr\NullsafeMethodCall $node): bool
    {
        $type = $this->getType($node->var);

        if (!$type instanceof FullyQualifiedObjectType) {
            return false;
        }

        try {
            (new \ReflectionClass($type->getClassName()))->getMethod((string)$node->name);

            return false;
        } catch (\ReflectionException) {
            return true;
        }
    }
}
