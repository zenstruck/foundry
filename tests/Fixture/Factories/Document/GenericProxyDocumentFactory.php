<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Tests\Fixture\Factories\Document;

use Zenstruck\Foundry\Tests\Fixture\Document\GenericDocument;
use Zenstruck\Foundry\Tests\Fixture\Factories\GenericProxyModelFactory;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class GenericProxyDocumentFactory extends GenericProxyModelFactory
{
    public static function class(): string
    {
        return GenericDocument::class;
    }
}
