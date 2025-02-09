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

namespace Zenstruck\Foundry\Tests\Integration\ResetDatabase;

use PHPUnit\Framework\Attributes\Test;
use Zenstruck\Foundry\Tests\Fixture\Document\GlobalDocument;
use Zenstruck\Foundry\Tests\Fixture\Entity\GlobalEntity;
use Zenstruck\Foundry\Tests\Fixture\FoundryTestKernel;
use Zenstruck\Foundry\Tests\Fixture\Stories\GlobalStory;

use function Zenstruck\Foundry\Persistence\repository;

final class GlobalStoryTest extends ResetDatabaseTestCase
{
    /**
     * @test
     */
    #[Test]
    public function global_stories_are_loaded(): void
    {
        if (FoundryTestKernel::hasORM()) {
            repository(GlobalEntity::class)->assert()->count(2);
        }

        if (FoundryTestKernel::hasMongo()) {
            repository(GlobalDocument::class)->assert()->count(2);
        }
    }

    /**
     * @test
     */
    #[Test]
    public function global_stories_cannot_be_loaded_again(): void
    {
        GlobalStory::load();

        if (FoundryTestKernel::hasORM()) {
            repository(GlobalEntity::class)->assert()->count(2);
        }

        if (FoundryTestKernel::hasMongo()) {
            repository(GlobalDocument::class)->assert()->count(2);
        }
    }
}
