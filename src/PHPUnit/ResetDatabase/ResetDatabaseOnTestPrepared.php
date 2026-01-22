<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\PHPUnit\ResetDatabase;

use PHPUnit\Event;
use PHPUnit\Event\Test\Prepared;
use Zenstruck\Foundry\Attribute\ResetDatabase;
use Zenstruck\Foundry\Persistence\ResetDatabase\ResetDatabaseManager;
use Zenstruck\Foundry\PHPUnit\AttributeReader;
use Zenstruck\Foundry\PHPUnit\KernelTestCaseHelper;

/**
 * @internal
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 */
final class ResetDatabaseOnTestPrepared implements Event\Test\PreparedSubscriber
{
    public function notify(Prepared $event): void
    {
        $test = $event->test();

        if (!$test->isTestMethod()) {
            return;
        }
        /** @var Event\Code\TestMethod $test */
        $resetDatabaseAttributes = AttributeReader::collectAttributesFromClassAndParents(
            ResetDatabase::class,
            new \ReflectionClass($test->className())
        );

        if ([] === $resetDatabaseAttributes || ResetDatabaseManager::canSkipSchemaReset()) {
            return;
        }

        ResetDatabaseManager::resetBeforeEachTest(
            KernelTestCaseHelper::bootKernel($test->className()),
        );
    }
}
