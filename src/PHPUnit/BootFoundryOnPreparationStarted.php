<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\PHPUnit;

use PHPUnit\Event;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Configuration;
use Zenstruck\Foundry\Test\UnitTestConfig;

/**
 * @internal
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 */
final class BootFoundryOnPreparationStarted implements Event\Test\PreparationStartedSubscriber
{
    public function notify(Event\Test\PreparationStarted $event): void
    {
        $test = $event->test();

        if (!$test->isTestMethod()) {
            return;
        }

        /** @var Event\Code\TestMethod $test */
        $this->bootFoundry($test->className());
    }

    /**
     * @param class-string $className
     */
    private function bootFoundry(string $className): void
    {
        if (!\is_subclass_of($className, TestCase::class)) {
            return;
        }

        // unit test
        if (!\is_subclass_of($className, KernelTestCase::class)) {
            Configuration::boot(UnitTestConfig::build());

            return;
        }

        // integration test
        Configuration::boot(static function() use ($className): Configuration {
            if (!KernelTestCaseHelper::getContainer($className)->has('.zenstruck_foundry.configuration')) {
                throw new \LogicException('ZenstruckFoundryBundle is not enabled. Ensure it is added to your config/bundles.php.');
            }

            return KernelTestCaseHelper::getContainer($className)->get('.zenstruck_foundry.configuration'); // @phpstan-ignore return.type
        });
    }
}
