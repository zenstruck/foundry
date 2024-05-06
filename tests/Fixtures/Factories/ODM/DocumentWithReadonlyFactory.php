<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Tests\Fixtures\Factories\ODM;

use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;
use Zenstruck\Foundry\Tests\Fixtures\PHP81\DocumentWithReadonly;

/**
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 */
final class DocumentWithReadonlyFactory extends PersistentProxyObjectFactory
{
    public static function class(): string
    {
        return DocumentWithReadonly::class;
    }

    protected function defaults(): array|callable
    {
        return [
            'prop' => 1,
            'embed' => UserFactory::new(['name' => 'foo']),
            'date' => new \DateTimeImmutable(),
        ];
    }
}
