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
use PHPStan\Type\ObjectType;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;
use Zenstruck\Foundry\Persistence\Proxy;

// we're not using Rector's built-in rule RenameMethodRector because it does not support NullsafeMethodCall
final class ChangeProxyMethodCalls extends AbstractRector
{
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Change from deprecated proxy methods to new methods.',
            [
                new CodeSample(
                    <<<'CODE_SAMPLE'
                        $proxy->object();
                        $proxy->save();
                        $proxy->remove();
                        $proxy->refresh();
                        $proxy->forceSet();
                        $proxy->forceGet();
                        $proxy->repository();
                        $proxy->enableAutoRefresh();
                        $proxy->disableAutoRefresh();
                        $proxy->withoutAutoRefresh();
                        $proxy->assertPersisted();
                        $proxy->assertNotPersisted();
                        CODE_SAMPLE,
                    <<<'CODE_SAMPLE'
                        $proxy->_real();
                        $proxy->_save();
                        $proxy->_delete();
                        $proxy->_refresh();
                        $proxy->_set();
                        $proxy->_get();
                        $proxy->_repository();
                        $proxy->_enableAutoRefresh();
                        $proxy->_disableAutoRefresh();
                        $proxy->_withoutAutoRefresh();
                        $proxy->_assertPersisted();
                        $proxy->_assertNotPersisted();
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
        if (!$this->isObjectType($node->var, new ObjectType(Proxy::class)) && !$this->isObjectType($node->var, new ObjectType(\Zenstruck\Foundry\Proxy::class))) {
            return null;
        }

        switch ($this->getName($node->name)) {
            case 'object':
                $node->name = new Node\Identifier('_real');

                return $node;
            case 'save':
                $node->name = new Node\Identifier('_save');

                return $node;
            case 'remove':
                $node->name = new Node\Identifier('_delete');

                return $node;
            case 'refresh':
                $node->name = new Node\Identifier('_refresh');

                return $node;
            case 'forceSet':
                $node->name = new Node\Identifier('_set');

                return $node;
            case 'forceGet':
                $node->name = new Node\Identifier('_get');

                return $node;
            case 'repository':
                $node->name = new Node\Identifier('_repository');

                return $node;
            case 'enableAutoRefresh':
                $node->name = new Node\Identifier('_enableAutoRefresh');

                return $node;
            case 'disableAutoRefresh':
                $node->name = new Node\Identifier('_disableAutoRefresh');

                return $node;
            case 'withoutAutoRefresh':
                $node->name = new Node\Identifier('_withoutAutoRefresh');

                return $node;
            case 'assertPersisted':
                $node->name = new Node\Identifier('_assertPersisted');

                return $node;
            case 'assertNotPersisted':
                $node->name = new Node\Identifier('_assertNotPersisted');

                return $node;
            default: return null;
        }
    }
}
