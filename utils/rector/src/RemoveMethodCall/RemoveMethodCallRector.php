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

namespace Zenstruck\Foundry\Utils\Rector\RemoveMethodCall;

use PhpParser\Node;
use PhpParser\NodeVisitor;
use Rector\Contract\Rector\ConfigurableRectorInterface;
use Rector\Rector\AbstractRector;
use Webmozart\Assert\Assert;

final class RemoveMethodCallRector extends AbstractRector implements ConfigurableRectorInterface
{
    /** @var RemoveMethodCall[] */
    private array $removeMethodCalls = [];

    /** @return array<class-string<Node>> */
    public function getNodeTypes() : array
    {
        return [Node\Stmt\Expression::class];
    }

    /** @param Node\Stmt\Expression $node */
    public function refactor(Node $node) : int|null
    {
        $method = $node->expr;

        if ($method instanceof Node\Expr\MethodCall && !$method->isFirstClassCallable() && $method->var instanceof Node\Expr\Variable) {
            foreach ($this->removeMethodCalls as $removeMethodCall) {
                if (!$this->isName($method->name, $removeMethodCall->methodName)) {
                    continue;
                }

                return NodeVisitor::REMOVE_NODE;
            }
        }

        return null;
    }

    /**
     * @param mixed[] $configuration
     */
    public function configure(array $configuration) : void
    {
        Assert::allIsInstanceOf($configuration, RemoveMethodCall::class);
        $this->removeMethodCalls = $configuration;
    }
}
