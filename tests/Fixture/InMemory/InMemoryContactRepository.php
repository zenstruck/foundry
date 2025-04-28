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

namespace Zenstruck\Foundry\Tests\Fixture\InMemory;

use Zenstruck\Foundry\InMemory\InMemoryRepository;
use Zenstruck\Foundry\InMemory\InMemoryRepositoryTrait;
use Zenstruck\Foundry\Tests\Fixture\Entity\Contact;

/**
 * @implements InMemoryRepository<Contact>
 */
final class InMemoryContactRepository implements InMemoryRepository
{
    /** @use InMemoryRepositoryTrait<Contact> */
    use InMemoryRepositoryTrait;

    public static function _class(): string
    {
        return Contact::class;
    }
}
