<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Tests\Integration\DataProvider;

use PHPUnit\Framework\Attributes\RequiresEnvironmentVariable;
use PHPUnit\Framework\Attributes\RequiresPhp;
use PHPUnit\Framework\Attributes\RequiresPhpunit;
use PHPUnit\Framework\Attributes\RequiresPhpunitExtension;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;
use Zenstruck\Foundry\PHPUnit\FoundryExtension;
use Zenstruck\Foundry\Tests\Fixture\Entity\EdgeCases\EntityWithReadonly\EntityWithReadonly;
use Zenstruck\Foundry\Tests\Fixture\Factories\Entity\GenericEntityFactory;
use Zenstruck\Foundry\Tests\Fixture\Model\Embeddable;
use Zenstruck\Foundry\Tests\Integration\RequiresORM;

use function Zenstruck\Foundry\Persistence\persistent_factory;

/**
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 * @requires PHPUnit >=12
 */
#[RequiresPhpunit('>=12')]
#[RequiresPhp('>=8.4')]
#[RequiresPhpunitExtension(FoundryExtension::class)]
#[RequiresEnvironmentVariable('USE_PHP_84_LAZY_OBJECTS', '1')]
final class DataProviderWithPersistentEntityFactoryTest extends DataProviderWithPersistentFactoryTestCase
{
    use RequiresORM;

    protected static function factory(): PersistentObjectFactory
    {
        return GenericEntityFactory::new();
    }

    /**
     * @return PersistentObjectFactory<EntityWithReadonly>
     */
    protected static function objectWithReadonlyFactory(): PersistentObjectFactory
    {
        return persistent_factory(EntityWithReadonly::class, [
            'prop' => 1,
            'embedded' => new Embeddable('value1'),
            'date' => new \DateTimeImmutable(),
        ]);
    }
}
