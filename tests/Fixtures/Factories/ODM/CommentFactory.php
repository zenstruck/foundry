<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Tests\Fixtures\Factories\ODM;

use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;
use Zenstruck\Foundry\Tests\Fixtures\Document\ODMComment;
use Zenstruck\Foundry\Tests\Fixtures\Document\ODMUser;

class CommentFactory extends PersistentProxyObjectFactory
{
    protected static function getClass(): string
    {
        return ODMComment::class;
    }

    protected function getDefaults(): array
    {
        return [
            'user' => new ODMUser(self::faker()->userName()),
            'body' => self::faker()->sentence(),
        ];
    }
}
