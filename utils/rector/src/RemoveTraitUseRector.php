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
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Trait_;
use PhpParser\Node\Stmt\TraitUse;
use Rector\Contract\Rector\ConfigurableRectorInterface;
use Rector\Rector\AbstractRector;

final class RemoveTraitUseRector extends AbstractRector implements ConfigurableRectorInterface
{
    /** @var string[] */
    private array $traitsToRemove = [];

    /** @return array<class-string<Node>> */
    public function getNodeTypes(): array
    {
        return [Class_::class, Trait_::class];
    }

    /** @param Class_|Trait_ $node */
    public function refactor(Node $node): ?Node
    {
        $hasChanged = false;

        foreach ($node->stmts as $key => $stmt) {
            if (!$stmt instanceof TraitUse) {
                continue;
            }

            foreach ($stmt->traits as $traitKey => $trait) {
                if (!$this->isNames($trait, $this->traitsToRemove)) {
                    continue;
                }

                unset($stmt->traits[$traitKey]);
                $hasChanged = true;
            }

            if ([] === $stmt->traits) {
                unset($node->stmts[$key]);
            }
        }

        return $hasChanged ? $node : null;
    }

    /** @param mixed[] $configuration */
    public function configure(array $configuration): void
    {
        foreach ($configuration as $traitName) {
            if (!\is_string($traitName)) {
                throw new \InvalidArgumentException(\sprintf('Expected trait name to be a string, got "%s".', \get_debug_type($traitName)));
            }
        }

        $this->traitsToRemove = $configuration;
    }
}
