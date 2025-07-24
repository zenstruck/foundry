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

namespace Zenstruck\Foundry\Utils\Rector\MethodCallToFuncCallWithObjectAsFirstParameter;

use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Name\FullyQualified;
use Rector\Contract\Rector\ConfigurableRectorInterface;
use Rector\Rector\AbstractRector;

final class MethodCallToFuncCallWithObjectAsFirstParameterRector extends AbstractRector implements ConfigurableRectorInterface
{
    /**
     * @var MethodCallToFuncCallWithObjectAsFirstParameter[]
     */
    private array $methodCallsToFuncCalls = [];
    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes() : array
    {
        return [MethodCall::class];
    }
    /**
     * @param MethodCall $node
     */
    public function refactor(Node $node) : ?Node
    {
        if ($node->isFirstClassCallable()) {
            return null;
        }

        foreach ($this->methodCallsToFuncCalls as $methodCallToFuncCall) {
            if (!$this->isName($node->name, $methodCallToFuncCall->methodName)) {
                continue;
            }
            return new FuncCall(new FullyQualified($methodCallToFuncCall->functionName), [new Arg($node->var), ...$node->getArgs()]);
        }
        return null;
    }
    /**
     * @param mixed[] $configuration
     */
    public function configure(array $configuration) : void
    {
        foreach ($configuration as $configItem) {
            if (!$configItem instanceof MethodCallToFuncCallWithObjectAsFirstParameter) {
                throw new \InvalidArgumentException(sprintf('Expected instance of "%s", got "%s".', MethodCallToFuncCallWithObjectAsFirstParameter::class, get_debug_type($configItem)));
            }
        }
        $this->methodCallsToFuncCalls = $configuration;
    }
}
