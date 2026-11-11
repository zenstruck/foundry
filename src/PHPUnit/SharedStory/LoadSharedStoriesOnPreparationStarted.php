<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\PHPUnit\SharedStory;

use DAMA\DoctrineTestBundle\Doctrine\DBAL\StaticDriver;
use DAMA\DoctrineTestBundle\PHPUnit\SkipDatabaseRollback;
use PHPUnit\Event;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Attribute\WithStory;
use Zenstruck\Foundry\Configuration;
use Zenstruck\Foundry\FakerAdapter;
use Zenstruck\Foundry\InMemory\AsInMemoryTest;
use Zenstruck\Foundry\Persistence\PersistenceManager;
use Zenstruck\Foundry\Persistence\ResetDatabase\ResetDatabaseManager;
use Zenstruck\Foundry\PHPUnit\AttributeReader;
use Zenstruck\Foundry\PHPUnit\KernelTestCaseHelper;
use Zenstruck\Foundry\Story;
use Zenstruck\Foundry\StoryRegistry;

/**
 * Loads the stories of a #[WithStory] declared on a class, or on a test method with a data provider, once for all
 * its tests, under a DAMA savepoint level the tests are rolled back to.
 * When this is not possible, they are loaded before each test, by BuildStoryOnTestPrepared.
 *
 * They are loaded before the first test rather than when the test suite starts: from there, DAMA's extension keeps
 * the savepoint level whatever the order the extensions are declared in.
 *
 * @internal
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 */
final class LoadSharedStoriesOnPreparationStarted implements Event\Test\PreparationStartedSubscriber
{
    private bool $ormOnly = true;

    public function notify(Event\Test\PreparationStarted $event): void
    {
        $test = $event->test();

        if (
            !$this->ormOnly
            || !$test instanceof Event\Code\TestMethod
            || !\is_subclass_of($test->className(), KernelTestCase::class)
            || !ResetDatabaseManager::isDAMADoctrineTestBundleEnabled()
            || !\method_exists(StaticDriver::class, 'createSavepoint') // @phpstan-ignore function.alreadyNarrowedType (DAMA without savepoint levels)
            || !$storiesByScope = self::storiesToShare($test)
        ) {
            return;
        }

        // the configuration of a previous kernel might still be booted, e.g. by the database reset
        Configuration::shutdown();
        KernelTestCaseHelper::bootKernel($test->className());

        try {
            // with another persistence layer, the database is reset before each test
            if (!$this->ormOnly = PersistenceManager::isOrmOnly()) {
                return;
            }

            foreach ($storiesByScope as $scope => $stories) {
                // what setUpBeforeClass() or the previous test left might not be rolled back yet
                if (StaticDriver::getSavepointLevel() > 0) {
                    StaticDriver::rollBackToSavepoint();
                } else {
                    StaticDriver::rollBack();
                    StaticDriver::beginTransaction();
                }

                // the stories get the same faker seed whatever the first test to run
                FakerAdapter::reset();
                FakerAdapter::setCurrentTestId($scope);

                Configuration::instance()->stories->loadSharedStories($scope, $stories);

                StaticDriver::createSavepoint();
            }
        } finally {
            KernelTestCaseHelper::ensureKernelShutdown($test->className());
            Configuration::shutdown();
        }
    }

    /**
     * @return array<string, list<class-string<Story>>> the stories which are not shared yet, by scope, outermost first
     */
    private static function storiesToShare(Event\Code\TestMethod $test): array
    {
        $class = new \ReflectionClass($test->className());
        $method = $class->getMethod($test->methodName());
        $withStoryAttributes = [];

        if (!$class->getAttributes(AsInMemoryTest::class) && !self::skipsDatabaseRollback($class, $class->getMethods())) {
            $withStoryAttributes[$class->name] = AttributeReader::collectAttributesFromClassAndParents(WithStory::class, $class);
        }

        if (
            $test->testData()->hasDataFromDataProvider()
            && !AsInMemoryTest::shouldEnableInMemory($class->name, $method->name)
            && !self::skipsDatabaseRollback($class, [$method])
        ) {
            $withStoryAttributes["{$class->name}::{$method->name}"] = $method->getAttributes(WithStory::class);
        }

        return \array_filter(
            \array_map(
                static fn(array $attributes) => \array_map(static fn(\ReflectionAttribute $attribute) => $attribute->newInstance()->story, $attributes),
                $withStoryAttributes,
            ),
            static fn(array $stories, string $scope) => $stories && !StoryRegistry::hasSharedStories($scope),
            \ARRAY_FILTER_USE_BOTH,
        );
    }

    /**
     * A test skipping DAMA's rollback uses other connections: they do not see the rows of the shared stories,
     * and might wait for the locks held on them.
     *
     * @param \ReflectionClass<object>  $class
     * @param list<\ReflectionMethod> $methods
     */
    private static function skipsDatabaseRollback(\ReflectionClass $class, array $methods): bool
    {
        return AttributeReader::classOrParentsHasAttribute($class->name, SkipDatabaseRollback::class)
            || \array_any($methods, static fn(\ReflectionMethod $method) => (bool) $method->getAttributes(SkipDatabaseRollback::class));
    }
}
