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
use PHPUnit\Event\Code\TestMethod;
use PHPUnit\Event\Test\Prepared;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Attribute\ResetDatabase;
use Zenstruck\Foundry\InMemory\AsInMemoryTest;
use Zenstruck\Foundry\Persistence\ResetDatabase\ResetDatabaseManager;
use Zenstruck\Foundry\PHPUnit\AttributeReader;
use Zenstruck\Foundry\PHPUnit\KernelTestCaseHelper;

/**
 * @internal
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 */
final readonly class ResetDatabaseOnTestPrepared implements Event\Test\PreparedSubscriber
{
    public function __construct(
        private bool $autoResetEnabled = false,
    ) {
    }

    public function notify(Prepared $event): void
    {
        $test = $event->test();

        if (!$test->isTestMethod()) {
            return;
        }

        if (!$this->shouldReset($test)) {
            return;
        }

        ResetDatabaseManager::resetBeforeEachTest(
            KernelTestCaseHelper::bootKernel($test->className()),
        );

        KernelTestCaseHelper::ensureKernelShutdown($test->className());
    }

    private function shouldReset(TestMethod $test): bool
    {
        $hasResetDatabaseAttribute = AttributeReader::classOrParentsHasAttribute($test->className(), ResetDatabase::class);

        if (!is_subclass_of($test->className(), KernelTestCase::class)) {
            if ($hasResetDatabaseAttribute) {
                throw new \LogicException(\sprintf('Class "%s" cannot use attribute #[ResetDatabase] if it does not extend "%s".', $test->className(), KernelTestCase::class));
            }

            return false;
        }

        if (
            AsInMemoryTest::shouldEnableInMemory($test->className(), $test->methodName())
            || ResetDatabaseManager::canSkipSchemaReset()
        ) {
            return false;
        }

        if ($this->autoResetEnabled) {
            return true;
        }

        return $hasResetDatabaseAttribute;
    }
}
