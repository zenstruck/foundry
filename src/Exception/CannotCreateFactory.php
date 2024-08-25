<?php

declare(strict_types=1);

namespace Zenstruck\Foundry\Exception;

/**
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 */
final class CannotCreateFactory extends \LogicException
{
    public static function argumentCountError(\ArgumentCountError $e): static
    {
        return new self('Factories with dependencies (services) cannot be created before foundry is booted.', previous: $e);
    }
}
