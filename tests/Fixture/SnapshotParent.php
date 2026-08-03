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
class SnapshotParent
{
    protected string $parentProtected = 'parent-protected';
    private string $parentPrivate = 'parent-private';
    private string $shadowed = 'parent-shadowed';

    public function parentPrivate(): string
    {
        return $this->parentPrivate;
    }

    public function parentProtected(): string
    {
        return $this->parentProtected;
    }

    public function parentShadowed(): string
    {
        return $this->shadowed;
    }
}
