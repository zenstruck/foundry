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
use PhpParser\Node\Identifier;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;
use Zenstruck\Foundry\Factory;
use Zenstruck\Foundry\Test\TestState;
use Zenstruck\Foundry\Test\UnitTestConfig;

final class ChangeFunctionsCalls extends AbstractRector
{
    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [Node\Expr\FuncCall::class, Node\Expr\StaticCall::class];
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Change method calls of legacy functions and static calls.',
            [
                new CodeSample(
                    <<<CODE_SAMPLE
                        use Zenstruck\\Foundry\\Factory;
                        use Zenstruck\\Foundry\\Test\\TestState;
                        use function Zenstruck\\Foundry\\create;
                        use function Zenstruck\\Foundry\\instantiate;
                        use function Zenstruck\\Foundry\repository;
                        create(SomeClass::class, []);
                        instantiate(SomeClass::class, ['published' => true]);
                        repository(\$someObject);
                        Factory::delayFlush(static fn() => true);
                        TestState::configure(faker: null);
                        CODE_SAMPLE,
                    <<<CODE_SAMPLE
                        \\Zenstruck\\Foundry\\Persistence\\persist_proxy(SomeClass::class, []);
                        \\Zenstruck\\Foundry\\Persistence\\proxy(\\Zenstruck\\Foundry\\object(SomeClass::class, ['published' => true]));
                        \\Zenstruck\\Foundry\\Persistence\repository(\$someObject);
                        \\Zenstruck\\Foundry\\Persistence\flush_after(static fn() => true);
                        \\Zenstruck\\Foundry\\Test\\UnitTestConfig::configure(faker: null);
                        CODE_SAMPLE
                ),
            ]
        );
    }

    public function refactor(Node $node): Node|int|null
    {
        return match ($node::class) {
            Node\Expr\FuncCall::class => $this->replaceFunctions($node),
            Node\Expr\StaticCall::class => $this->replaceLegacyMethodCalls($node),
            default => null,
        };
    }

    private function replaceFunctions(Node\Expr\FuncCall $node): ?Node
    {
        if (!$node->name instanceof Node\Name) {
            return null;
        }

        $name = $node->name->getAttribute('namespacedName') ?? $this->getName($node->name);

        switch ($name) {
            case 'Zenstruck\Foundry\create':
                $node->name = new Node\Name\FullyQualified('Zenstruck\Foundry\Persistence\persist_proxy');

                return $node;
            case 'Zenstruck\Foundry\instantiate':
                $node->name = new Node\Name\FullyQualified('Zenstruck\Foundry\object');

                return new Node\Expr\FuncCall(new Node\Name\FullyQualified('Zenstruck\Foundry\Persistence\proxy'), [new Node\Arg($node)]);
            case 'Zenstruck\Foundry\repository':
                $node->name = new Node\Name\FullyQualified('Zenstruck\Foundry\Persistence\repository');

                return $node;
            default:
                return null;
        }
    }

    private function replaceLegacyMethodCalls(Node\Expr\StaticCall $node): ?Node
    {
        if (
            $node->name instanceof Identifier
            && 'delayFlush' === $this->getName($node->name)
            && $node->class instanceof Node\Name
            && \is_a((string) $node->class, Factory::class, allow_string: true)
        ) {
            return new Node\Expr\FuncCall(new Node\Name\FullyQualified('Zenstruck\Foundry\Persistence\flush_after'), $node->args);
        }

        if (
            $node->name instanceof Identifier
            && 'configure' === $this->getName($node->name)
            && $node->class instanceof Node\Name
            && TestState::class === (string) $node->class
        ) {
            return new Node\Expr\StaticCall(new Node\Name\FullyQualified(UnitTestConfig::class), 'configure', $node->args);
        }

        return null;
    }
}
