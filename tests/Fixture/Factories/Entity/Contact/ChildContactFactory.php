<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Tests\Fixture\Factories\Entity\Contact;

use Zenstruck\Foundry\Tests\Fixture\Entity\Contact\ChildContact;

final class ChildContactFactory extends StandardContactFactory
{
    public static function class(): string
    {
        return ChildContact::class;
    }
}
