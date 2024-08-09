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

namespace Zenstruck\Foundry\Test\PHPUnit;

use PHPUnit\Event;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpKernel\KernelInterface;
use Zenstruck\Foundry\Configuration;
use Zenstruck\Foundry\Test\UnitTestConfig;

/**
 * @internal
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 */
final class BootFoundryOnDataProviderMethodCalled implements Event\Test\DataProviderMethodCalledSubscriber
{
    public function __construct(
        private KernelInterface $kernel,
    ) {
    }

    public function notify(Event\Test\DataProviderMethodCalled $event): void
    {
        if (\is_a($event->testMethod()->className(), KernelTestCase::class, allow_string: true)) {
            static $kernelIsBooted = false;

            if (!$kernelIsBooted) {
                $this->kernel->boot();
                $kernelIsBooted = true;
            }

            Configuration::bootForDataProvider(
                fn() => $this->kernel->getContainer()->get('.zenstruck_foundry.configuration'),
            );
        } else {
            Configuration::bootForDataProvider(UnitTestConfig::build());
        }
    }
}
