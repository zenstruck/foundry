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

namespace Zenstruck\Foundry\Test\Behat;

/**
 * @internal
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 */
final class ObjectNotFoundException extends \RuntimeException
{
    public static function forFactoryAndName(string $factoryShortName, string $name): self
    {
        return new self("Object \"$factoryShortName $name\" was not found.");
    }

    /**
     * @param class-string $objectName
     */
    public static function forClassAndName(string $objectName, string $name): self
    {
        return new self("Object of class \"$objectName\" with name \"$name\" was not found.");
    }

    public static function objectReferencedInTableDoesNotExist(string $column, self $previous): self
    {
        return new self("A reference to an object cannot be resolved in the table, at \"$column\": {$previous->getMessage()}", previous: $previous);
    }
}
