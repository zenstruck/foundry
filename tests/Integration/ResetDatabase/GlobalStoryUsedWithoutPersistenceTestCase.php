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
use PHPUnit\Framework\TestCase;
use Zenstruck\Foundry\Story;
use Zenstruck\Foundry\StoryRegistry;
use Zenstruck\Foundry\Tests\Fixture\FoundryTestKernel;
use Zenstruck\Foundry\Tests\Fixture\Stories\GlobalStory;

/**
 * Tests that must NOT extend KernelTestCase: they assert global stories don't
 * leak into tests running without persistence in the same process.
 *
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 */
abstract class GlobalStoryUsedWithoutPersistenceTestCase extends TestCase
{
    #[Test]
    public function global_story_is_rebuilt_locally_when_persistence_is_not_available(): void
    {
        /** @var array<string, Story> $globalInstancesBackup */
        $globalInstancesBackup = self::globalInstancesProperty()->getValue();
        $globalInstance = $globalInstancesBackup[GlobalStory::class] ?? null;

        if (!$globalInstance) {
            // when the whole "reset-database" suite runs, the global stories have already
            // been loaded by a previous kernel test in this process. When this test runs
            // alone (ie: with --filter), seed the registry to simulate this situation.
            $globalInstance = new GlobalStory();
            $globalInstance->build();

            self::globalInstancesProperty()->setValue(null, [GlobalStory::class => $globalInstance] + $globalInstancesBackup);
        }

        try {
            $story = GlobalStory::load();

            self::assertNotSame($globalInstance, $story);
            self::assertSame($story, GlobalStory::load());

            if (FoundryTestKernel::hasORM()) {
                self::assertNull(GlobalStory::globalEntity()->id, 'The story state should have been rebuilt locally, without persistence.');
            }

            if (FoundryTestKernel::hasMongo()) {
                self::assertNull(GlobalStory::globalDocument()->id, 'The story state should have been rebuilt locally, without persistence.');
            }
        } finally {
            self::globalInstancesProperty()->setValue(null, $globalInstancesBackup);
        }
    }

    private static function globalInstancesProperty(): \ReflectionProperty
    {
        return new \ReflectionProperty(StoryRegistry::class, 'globalInstances');
    }
}
