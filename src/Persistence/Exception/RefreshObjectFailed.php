<?php

declare(strict_types=1);

namespace Zenstruck\Foundry\Persistence\Exception;

final class RefreshObjectFailed extends \RuntimeException
{
    public static function objectNoLongExists(): static
    {
        return new self('object no longer exists...');
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
}
