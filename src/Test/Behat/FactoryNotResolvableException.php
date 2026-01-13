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
final class FactoryNotResolvableException extends \RuntimeException
{
    public static function forName(string $name): self
    {
        return new self("Cannot resolve factory for \"$name\": short name does not exist");
    }

    /**
     * @param list<class-string> $factories
     */
    public static function conflict(string $name, array $factories): self
    {
        return new self(\sprintf(
            'Multiple factories found for "%s": %s. Use #[FactoryShortName] to disambiguate.',
            $name,
            \implode(', ', $factories)
        ));
    }
}
