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
use Rector\Rector\AbstractRector;
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
        if ('faker' !== $this->getName($node->name)) {
            return null;
        }

        // if method is not called on a Factory class, then do nothing
        if (
            !$node->class instanceof Node\Name
            || !\is_a((string) $node->class, Factory::class, allow_string: true)
        ) {
            return null;
        }

        /** @var MutatingScope $mutatingScope */
        $mutatingScope = $node->getAttribute('scope');

        // if the Factory::faker() was called from a Factory, then do nothing
        if (
            null !== ($classReflection = $mutatingScope->getClassReflection())
            && \is_a($classReflection->getName(), Factory::class, allow_string: true)
        ) {
            return null;
        }

        return new Node\Expr\FuncCall(new Node\Name('\Zenstruck\Foundry\faker'));
    }
}
