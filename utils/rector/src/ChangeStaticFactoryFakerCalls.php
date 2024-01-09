<?php

declare(strict_types=1);

namespace Zenstruck\Foundry\Utils\Rector;

use PhpParser\Node;
use PHPStan\Analyser\MutatingScope;
use Rector\Core\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;
use Zenstruck\Foundry\Factory;

final class ChangeStaticFactoryFakerCalls extends AbstractRector
{
    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [Node\Expr\StaticCall::class];
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Change Factory::faker() calls, outside from a factory (method is not protected.',
            [
                new CodeSample(
                    <<<'CODE_SAMPLE'
                    // not in a factory class
                    Factory::faker();
                    CODE_SAMPLE,
                    <<<'CODE_SAMPLE'
                    // not in a factory class
                    \Zenstruck\Foundry\faker();
                    CODE_SAMPLE
                ),
            ]
        );
    }

    /**
     * @param Node\Expr\StaticCall $node
     */
    public function refactor(Node $node): Node|int|null
    {
        // if method name is not faker, then do nothing
        if ((string)$node->name !== 'faker') {
            return null;
        }

        // if method is not called on a Factory class, then do nothing
        if (
            !$node->class instanceof Node\Name
            || !is_a((string)$node->class, Factory::class, allow_string: true)
        ) {
            return null;
        }

        /** @var MutatingScope */
        $mutatingScope = $node->getAttribute('scope');

        // if the Factory::faker() was called from a Factory, then do nothing
        if (
            null !== ($classReflection = $mutatingScope->getClassReflection())
            && is_a($classReflection->getName(), Factory::class, allow_string: true)
        ) {
            return null;
        }

        return new Node\Expr\FuncCall(new Node\Name('\Zenstruck\Foundry\faker'));
    }
}
