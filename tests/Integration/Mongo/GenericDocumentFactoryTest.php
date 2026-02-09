<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Tests\Integration\Mongo;

use PHPUnit\Framework\Attributes\RequiresEnvironmentVariable;
use Zenstruck\Foundry\Tests\Fixture\Factories\Document\GenericDocumentFactory;
use Zenstruck\Foundry\Tests\Integration\Persistence\GenericFactoryTestCase;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
#[RequiresEnvironmentVariable('MONGO_URL')]
final class GenericDocumentFactoryTest extends GenericFactoryTestCase
{
    protected static function factory(): GenericDocumentFactory
    {
        return GenericDocumentFactory::new();
    }
}
