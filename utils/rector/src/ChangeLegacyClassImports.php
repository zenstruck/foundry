<?php

declare(strict_types=1);

namespace Zenstruck\Foundry\Utils\Rector;

use PhpParser\Node;
use PHPStan\Type\ObjectType;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;
use Zenstruck\Foundry\Object\Instantiator;
use Zenstruck\Foundry\Persistence\Proxy;
use Zenstruck\Foundry\Persistence\RepositoryAssertions;
use Zenstruck\Foundry\Persistence\RepositoryDecorator;

/**
 * Let's only change type hints in "use" statements
 *
 * We don't check for FQCN which would be used directly in PHPdoc, return types and parameter types,
 * that would increase the complexity by a lot, for something people are not very likely to use.
 */
final class ChangeLegacyClassImports extends AbstractRector
{
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Change FQCN in imports for some deprecated classes with their replacements.',
            [
                new CodeSample(
                    <<<'CODE_SAMPLE'
                    use Zenstruck\Foundry\Proxy;
                    use Zenstruck\Foundry\Instantiator;
                    use Zenstruck\Foundry\RepositoryProxy;
                    use Zenstruck\Foundry\RepositoryAssertions;
                    CODE_SAMPLE,
                    <<<'CODE_SAMPLE'
                    use Zenstruck\Foundry\Persistence\Proxy;
                    use Zenstruck\Foundry\Object\Instantiator;
                    use Zenstruck\Foundry\Persistence\RepositoryDecorator;
                    use Zenstruck\Foundry\Persistence\RepositoryAssertions;
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
        return [Node\Stmt\UseUse::class];
    }

    /**
     * @param Node\Stmt\UseUse $node
     */
    public function refactor(Node $node): ?Node
    {
        switch((string)$node->name){
            case \Zenstruck\Foundry\Proxy::class:
                $node->name = new Node\Name(Proxy::class);
                return $node;
            case \Zenstruck\Foundry\Instantiator::class:
                $node->name = new Node\Name(Instantiator::class);
                return $node;
            case \Zenstruck\Foundry\RepositoryProxy::class:
                $node->name = new Node\Name(RepositoryDecorator::class);
                return $node;
            case \Zenstruck\Foundry\RepositoryAssertions::class:
                $node->name = new Node\Name(RepositoryAssertions::class);
                return $node;
            default: return null;
        }
    }
}
