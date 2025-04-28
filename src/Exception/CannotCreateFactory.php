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

namespace Zenstruck\Foundry\Exception;

/**
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 * @internal
 */
final class CannotCreateFactory extends \LogicException
{
    public static function argumentCountError(\ArgumentCountError $e): static
    {
        return new self('Factories with dependencies (services) cannot be created before foundry is booted.', previous: $e);
    }
}
