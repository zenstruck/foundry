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

namespace Zenstruck\Foundry\Test\Behat\Exception;

/**
 * @internal
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 */
final class ObjectAlreadyRegistered extends \RuntimeException
{
    /** @param class-string $objectClass */
    public static function forClassAndName(string $objectClass, string $name): self
    {
        return new self("Object \"{$name}\" is already registered for class \"{$objectClass}\". This may happen when loading multiple Stories in a group that define objects with the same name.");
    }
}
