<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Tests\Fixture;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class SnapshotChild extends SnapshotParent
{
    public string $childPublic = 'child-public';
    private string $childPrivate = 'child-private';
    private string $shadowed = 'child-shadowed';

    public function childPrivate(): string
    {
        return $this->childPrivate;
    }

    public function childShadowed(): string
    {
        return $this->shadowed;
    }
}
