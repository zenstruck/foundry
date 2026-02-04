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
final class InvalidObjectParameter extends \RuntimeException
{
    public static function objectReferencedInTableDoesNotExist(string $column, ObjectNotFound $previous): self
    {
        return new self("A reference to an object cannot be resolved in the table, at column \"$column\": {$previous->getMessage()}", previous: $previous);
    }

    public static function invalidDate(string $column, string $invalidDate, \Throwable $previous): self
    {
        return new self("Invalid date given \"$invalidDate\", at column \"$column\"", previous: $previous);
    }

    public static function invalidEnumValue(string $column, string $invalidEnumValue): self
    {
        return new self("Invalid enum value given \"$invalidEnumValue\", at column \"$column\"");
    }
}
