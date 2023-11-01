<?php

declare(strict_types=1);

namespace Zenstruck\Foundry\Utils\Rector;

use PhpParser\Node;
use PhpParser\Node\Name\FullyQualified;
use PHPStan\Analyser\Scope;
use PHPStan\Type\ObjectType;
use Rector\Rector\AbstractScopeAwareRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Object\Instantiator;
use Zenstruck\Foundry\ObjectFactory;

final class ChangeInstantiatorMethodCalls extends AbstractScopeAwareRector
{
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Create Instantiator with named constructor + change legacy methods.',
            [
                new CodeSample(
                    <<<'CODE_SAMPLE'
                    (new Instantiator())
                        ->allowExtraAttributes(['some', 'fields'])
                        ->alwaysForceProperties(['other', 'fields'])
                        ->allowExtraAttributes()
                        ->alwaysForceProperties()
                    CODE_SAMPLE,
                    <<<'CODE_SAMPLE'
                    (\Zenstruck\Foundry\Object\Instantiator::withConstructor())
                        ->allowExtra(...['some', 'fields'])
                        ->alwaysForce(...['other', 'fields'])
                        ->allowExtra()
                        ->alwaysForce()
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
        return [Node\Expr\MethodCall::class, Node\Expr\New_::class];
    }

    public function refactorWithScope(Node $node, Scope $scope)
    {
        return match(true){
            $node instanceof Node\Expr\MethodCall => $this->changeMethodCalls($node),
            $node instanceof Node\Expr\New_ => $this->useNamedConstructor($node, $scope),
            default => null
        };
    }

    /**
     * We cannot remove `->withoutConstructor()` calls without risk, so users should rely on deprecations
     */
    private function changeMethodCalls(Node\Expr\MethodCall $node): Node|null
    {
        if (!$this->isObjectType($node->var, new ObjectType(Instantiator::class))) {
            return null;
        }

        switch((string)$node->name){
            case 'allowExtraAttributes':
                $node->name = new Node\Identifier('allowExtra');
                if (count($node->args) === 1) {
                    $node->args[0]->unpack = true;
                }

                return $node;
            case 'alwaysForceProperties':
                $node->name = new Node\Identifier('alwaysForce');
                if (count($node->args) === 1) {
                    $node->args[0]->unpack = true;
                }

                return $node;
            default: return null;
        }
    }

    private function useNamedConstructor(Node\Expr\New_ $node, Scope $scope): Node|null
    {
        if (!$node->class instanceof FullyQualified) {
            return null;
        }

        if (!is_a($node->class->toString(), Instantiator::class, allow_string: true)) {
            return null;
        }

        $factoryClass = $scope->getClassReflection()?->getName();
        if ($factoryClass && is_a($factoryClass, ObjectFactory::class, allow_string: true)) {
            $targetClass = is_a($factoryClass, ModelFactory::class, allow_string: true)
                ? (new \ReflectionClass($factoryClass))->getMethod('getClass')->invoke(null)
                : $factoryClass::class();
            $targetClassConstructorIsPublic = (new \ReflectionClass($targetClass))->getConstructor()?->isPublic() ?? true;

            if (!$targetClassConstructorIsPublic) {
                /**
                 * The only case where we can safely use `withoutConstructor()` is when target's class' constructor
                 * is not public: foundry 1.x fallbacks on "withoutConstructor" behavior,
                 * while foundry 2 throws an exception.
                 */
                return new Node\Expr\StaticCall(new Node\Name('\\'.Instantiator::class), 'withoutConstructor');
            }
        }

        return new Node\Expr\StaticCall(new Node\Name('\\'.Instantiator::class), 'withConstructor');
    }
}
