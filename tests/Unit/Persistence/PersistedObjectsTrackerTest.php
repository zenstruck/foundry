<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Tests\Unit\Persistence;

use Faker;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Zenstruck\Foundry\Configuration;
use Zenstruck\Foundry\FactoryRegistry;
use Zenstruck\Foundry\FakerAdapter;
use Zenstruck\Foundry\Object\Instantiator;
use Zenstruck\Foundry\Persistence\PersistedObjectsTracker;
use Zenstruck\Foundry\Persistence\PersistenceManager;
use Zenstruck\Foundry\StoryRegistry;

final class PersistedObjectsTrackerTest extends TestCase
{
    protected function tearDown(): void
    {
        Configuration::shutdown();
    }

    #[Test]
    public function it_tracks_objects_of_a_backend_supporting_auto_refresh(): void
    {
        $tracker = $this->bootWith(supportsAutoRefresh: true);

        $tracker->add(new \stdClass());

        self::assertSame(1, PersistedObjectsTracker::countObjects());
    }

    #[Test]
    public function it_skips_objects_of_a_backend_without_auto_refresh(): void
    {
        $tracker = $this->bootWith(supportsAutoRefresh: false);

        $tracker->add(new \stdClass());

        self::assertSame(0, PersistedObjectsTracker::countObjects());
    }

    private function bootWith(bool $supportsAutoRefresh): PersistedObjectsTracker
    {
        $persistence = self::createStub(PersistenceManager::class);
        $persistence->method('hasPersistenceFor')->willReturn(true);
        $persistence->method('supportsAutoRefresh')->willReturn($supportsAutoRefresh);
        $persistence->method('getIdentifierValues')->willReturn(['id' => 1]);

        $tracker = new PersistedObjectsTracker();

        Configuration::boot(new Configuration(
            new FactoryRegistry([]),
            new FakerAdapter(Faker\Factory::create()),
            Instantiator::withConstructor(),
            new StoryRegistry([]),
            persistence: $persistence,
            persistedObjectsTracker: $tracker,
        ));

        return $tracker;
    }
}
