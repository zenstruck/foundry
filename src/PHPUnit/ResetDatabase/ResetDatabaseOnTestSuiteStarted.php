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
use Zenstruck\Foundry\PHPUnit\FoundryExtension;
use Zenstruck\Foundry\PHPUnit\KernelTestCaseHelper;

/**
 * @internal
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 */
final class ResetDatabaseOnTestSuiteStarted implements Event\TestSuite\StartedSubscriber
{
    public function __construct(
        private readonly bool $autoResetEnabled = false,
        private readonly bool $exitOnFailure = false,
    ) {
    }

    public function notify(Event\TestSuite\Started $event): void
    {
        if (!$event->testSuite()->isForTestClass()) {
            return;
        }

        if (ResetDatabaseManager::databaseHasBeenResetBeforeFirstTest()) {
            return;
        }

        $testClassName = $event->testSuite()->name();

        if (!\class_exists($testClassName)) {
            return;
        }

        if (!$this->shouldReset($testClassName)) {
            return;
        }

        try {
            ResetDatabaseManager::resetBeforeFirstTest(
                KernelTestCaseHelper::bootKernel($testClassName),
            );
        } catch (\Throwable $e) {
            if (!$this->exitOnFailure) {
                throw $e;
            }

            // PHPUnit would only report a warning and keep running the tests against a database in an unknown state
            $parameter = FoundryExtension::PARAMETER_EXIT_ON_RESET_DATABASE_FAILURE;

            \fwrite(\STDERR, <<<MESSAGE


                Foundry could not reset the database before the first test of "{$testClassName}": the test suite has been stopped.

                {$e}

                Set the "{$parameter}" parameter of the Foundry PHPUnit extension to "false" to run the tests anyway.

                MESSAGE);

            exit(2);
        }

        KernelTestCaseHelper::ensureKernelShutdown($testClassName);
    }

    /**
     * @param class-string $testClassName
     */
    private function shouldReset(string $testClassName): bool
    {
        if (!\is_subclass_of($testClassName, KernelTestCase::class)) {
            return false;
        }

        if ($this->autoResetEnabled) {
            return true;
        }

        return AttributeReader::classOrParentsHasAttribute($testClassName, ResetDatabase::class)

            // let's use ResetDatabase trait as a marker, the same way we're using the attribute
            || (new \ReflectionClass($testClassName))->hasMethod('_resetDatabaseBeforeFirstTest');
    }
}
