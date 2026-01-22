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
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Attribute\ResetDatabase;
use Zenstruck\Foundry\Persistence\ResetDatabase\ResetDatabaseManager;
use Zenstruck\Foundry\PHPUnit\AttributeReader;
use Zenstruck\Foundry\PHPUnit\KernelTestCaseHelper;

/**
 * @internal
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 */
final readonly class ResetDatabaseOnTestSuiteStarted implements Event\TestSuite\StartedSubscriber
{
    public function __construct(
        private bool $autoResetEnabled = false,
    ) {
    }

    public function notify(Event\TestSuite\Started $event): void
    {
        if (!$event->testSuite()->isForTestClass()) {
            return;
        }

        $testClassName = $event->testSuite()->name();

        if (
            !\class_exists($testClassName)
            || !$this->shouldReset($testClassName)
        ) {
            return;
        }

        ResetDatabaseManager::resetBeforeFirstTest(
            KernelTestCaseHelper::bootKernel($testClassName),
        );

        KernelTestCaseHelper::ensureKernelShutdown($testClassName);
    }

    /**
     * @param class-string $testClassName
     */
    private function shouldReset(string $testClassName): bool
    {
        if (!is_subclass_of($testClassName, KernelTestCase::class)) {
            return false;
        }

        if ($this->autoResetEnabled) {
            return true;
        }

        return AttributeReader::classOrParentsHasAttribute($testClassName, ResetDatabase::class);
    }
}
