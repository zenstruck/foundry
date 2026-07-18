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

namespace Zenstruck\Foundry\Tests\Fixture\DoctrineEvents;

use Zenstruck\Foundry\Persistence\PersistentObjectFactory;
use Zenstruck\Foundry\Tests\Fixture\Document\DocumentWithListenedRelation;

/**
 * @extends PersistentObjectFactory<DocumentWithListenedRelation>
 */
final class DocumentWithListenedRelationFactory extends PersistentObjectFactory
{
    public static function class(): string
    {
        return DocumentWithListenedRelation::class;
    }

    protected function defaults(): array
    {
        return [
            'name' => self::faker()->word(),
            'listened' => DocumentForDoctrineEventsFactory::new(),
        ];
    }
}
