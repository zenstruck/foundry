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
use Zenstruck\Foundry\Tests\Fixture\Stories\DocumentPoolStory;
use Zenstruck\Foundry\Tests\Fixture\Stories\DocumentStory;
use Zenstruck\Foundry\Tests\Integration\Persistence\StoryTestCase;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
#[RequiresEnvironmentVariable('MONGO_URL')]
final class MongoStoryTest extends StoryTestCase
{
    protected static function storyClass(): string
    {
        return DocumentStory::class;
    }

    protected static function factoryClass(): string
    {
        return GenericDocumentFactory::class;
    }

    protected static function poolStoryClass(): string
    {
        return DocumentPoolStory::class;
    }
}
