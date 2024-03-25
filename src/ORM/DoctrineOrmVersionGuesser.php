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

namespace Zenstruck\Foundry\ORM;

use Doctrine\ORM\Mapping\FieldMapping;

final class DoctrineOrmVersionGuesser
{
    public static function isOrmV3(): bool
    {
        return \class_exists(FieldMapping::class);
    }
}
