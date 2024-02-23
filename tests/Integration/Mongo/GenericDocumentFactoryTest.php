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

use Zenstruck\Foundry\Tests\Fixture\Factories\Document\GenericDocumentFactory;
use Zenstruck\Foundry\Tests\Fixture\Factories\GenericModelFactory;
use Zenstruck\Foundry\Tests\Integration\Persistence\GenericFactoryTestCase;
use Zenstruck\Foundry\Tests\Integration\RequiresMongo;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class GenericDocumentFactoryTest extends GenericFactoryTestCase
{
    use RequiresMongo;

    protected function factory(): GenericModelFactory
    {
        return GenericDocumentFactory::new();
    }
}
