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
use PHPUnit\Event;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Attribute\WithStory;
use Zenstruck\Foundry\Configuration;
use Zenstruck\Foundry\InMemory\AsInMemoryTest;
use Zenstruck\Foundry\Persistence\PersistenceManager;
use Zenstruck\Foundry\PHPUnit\AttributeReader;
use Zenstruck\Foundry\PHPUnit\KernelTestCaseHelper;

/**
 * Loads the stories of a #[WithStory] declared on a class, or on a test method with a data provider, once for all
 * its tests, under a DAMA savepoint level the tests are rolled back to.
 * When this is not possible, they are loaded before each test, by BuildStoryOnTestPrepared.
 *
 * @internal
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 */
final class LoadSharedStoriesOnTestSuiteStarted implements Event\TestSuite\StartedSubscriber
{
    public function notify(Event\TestSuite\Started $event): void
    {
        $suite = $event->testSuite();

        [$class, $method] = match (true) {
            $suite instanceof Event\TestSuite\TestSuiteForTestClass => [$suite->className(), null],
            $suite instanceof Event\TestSuite\TestSuiteForTestMethodWithDataProvider => [$suite->className(), $suite->methodName()],
            default => [null, null],
        };

        if (!$class || !\is_subclass_of($class, KernelTestCase::class) || !self::canShareDatabaseState()) {
            return;
        }

        $reflectionClass = new \ReflectionClass($class);
        $stories = \array_map(
            static fn(\ReflectionAttribute $attribute) => $attribute->newInstance()->story,
            $method
                ? $reflectionClass->getMethod($method)->getAttributes(WithStory::class)
                : AttributeReader::collectAttributesFromClassAndParents(WithStory::class, $reflectionClass),
        );

        if (!$stories || ($method ? AsInMemoryTest::shouldEnableInMemory($class, $method) : $reflectionClass->getAttributes(AsInMemoryTest::class))) {
            return;
        }

        // the configuration of a previous kernel might still be booted, e.g. by the database reset
        Configuration::shutdown();
        KernelTestCaseHelper::bootKernel($class);

        try {
            if (!PersistenceManager::isOrmOnly()) {
                return;
            }

            // the changes of the previous test are only rolled back before the next one
            if (StaticDriver::getSavepointLevel() > 0) {
                StaticDriver::rollBackToSavepoint();
            } else {
                StaticDriver::rollBack();
                StaticDriver::beginTransaction();
            }

            Configuration::instance()->stories->loadSharedStories($suite->name(), \array_values(\array_unique($stories)));

            StaticDriver::createSavepoint();
        } finally {
            KernelTestCaseHelper::ensureKernelShutdown($class);
            Configuration::shutdown();
        }
    }

    private static function canShareDatabaseState(): bool
    {
        return \class_exists(StaticDriver::class)
            && \method_exists(StaticDriver::class, 'createSavepoint') // @phpstan-ignore function.alreadyNarrowedType (DAMA without savepoint levels)
            && StaticDriver::isKeepStaticConnections(); // DAMA's PHPUnit extension is enabled, and not skipping the rollback
    }
}
