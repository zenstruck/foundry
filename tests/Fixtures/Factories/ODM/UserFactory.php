<?php

namespace Zenstruck\Foundry\Tests\Fixtures\Factories\ODM;

use Zenstruck\Foundry\Persistence\PersistentObjectFactory;
use Zenstruck\Foundry\Tests\Fixtures\Document\ODMUser;

/**
 * This factory should extend ObjectFactory because ODMUser class is not "persistable" but let's keep it this way
 * in order to test legacy behavior with EmbeddedDocument.
 */
final class UserFactory extends PersistentObjectFactory
{
    public static function class(): string
    {
        return ODMUser::class;
    }

    protected function getDefaults(): array
    {
        return [];
    }
}
