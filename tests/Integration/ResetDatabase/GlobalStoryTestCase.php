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

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Tests\Fixture\Document\GlobalDocument;
use Zenstruck\Foundry\Tests\Fixture\Entity\GlobalEntity;
use Zenstruck\Foundry\Tests\Fixture\FoundryTestKernel;
use Zenstruck\Foundry\Tests\Fixture\ResetDatabase\ResetDatabaseTestKernel;
use Zenstruck\Foundry\Tests\Fixture\Stories\GlobalStory;

use function Zenstruck\Foundry\Persistence\repository;

abstract class GlobalStoryTestCase extends KernelTestCase
{
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

    #[Test]
    public function story_states_are_managed_by_the_current_object_manager(): void
    {
        if (FoundryTestKernel::hasORM()) {
            $em = self::getContainer()->get(EntityManagerInterface::class);
            \assert($em instanceof EntityManagerInterface);

            self::assertTrue($em->contains(GlobalStory::globalEntity()));
            self::assertTrue($em->contains(GlobalStory::getRandom('globalEntities')));

            $arrayState = GlobalStory::get('globalEntitiesArray');
            self::assertIsArray($arrayState);
            self::assertTrue($em->contains($arrayState[0]));
        }

        if (FoundryTestKernel::hasMongo()) {
            $dm = self::getContainer()->get(DocumentManager::class);
            \assert($dm instanceof DocumentManager);

            self::assertTrue($dm->contains(GlobalStory::globalDocument()));
            self::assertTrue($dm->contains(GlobalStory::getRandom('globalDocuments')));

            $arrayState = GlobalStory::get('globalDocumentsArray');
            self::assertIsArray($arrayState);
            self::assertTrue($dm->contains($arrayState[0]));
        }
    }

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

    protected static function getKernelClass(): string
    {
        return ResetDatabaseTestKernel::class;
    }
}
