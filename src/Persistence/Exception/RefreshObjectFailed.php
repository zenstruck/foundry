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

namespace Zenstruck\Foundry\Persistence\Exception;

final class RefreshObjectFailed extends \RuntimeException
{
    private function __construct(string $message, private bool $objectWasDeleted = false)
    {
        parent::__construct($message);
    }

    public static function objectNoLongExists(): static
    {
        return new self('object no longer exists...', objectWasDeleted: true);
    }

    /**
     * @param class-string $objectClass
     */
    public static function objectHasUnsavedChanges(string $objectClass): static
    {
        return new self(
            "Cannot auto refresh \"{$objectClass}\" as there are unsaved changes. Be sure to call ->_save() or disable auto refreshing (see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#auto-refresh for details)."
        );
    }

    public function objectWasDeleted(): bool
    {
        return $this->objectWasDeleted;
    }
}
