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

namespace Zenstruck\Foundry\PHPUnit;

use PHPUnit\Metadata\Version\ConstraintRequirement;
use PHPUnit\Runner;
use PHPUnit\TextUI;
use Zenstruck\Foundry\Configuration;

/**
 * @internal
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 */
final class FoundryExtension implements Runner\Extension\Extension
{
    public function bootstrap(
        TextUI\Configuration\Configuration $configuration,
        Runner\Extension\Facade $facade,
        Runner\Extension\ParameterCollection $parameters,
    ): void {
        // shutdown Foundry if for some reason it has been booted before
        if (Configuration::isBooted()) {
            Configuration::shutdown();
        }

        $subscribers = [new BuildStoryOnTestPrepared(), new DisplayFakerSeedOnTestSuiteFinished()];

        if (ConstraintRequirement::from('>=11.4')->isSatisfiedBy(Runner\Version::id())) {
            // those deal with data provider events which can be useful only if PHPUnit >=11.4 is used
            $subscribers[] = new BootFoundryOnDataProviderMethodCalled();
            $subscribers[] = new ShutdownFoundryOnDataProviderMethodFinished();
        }

        $facade->registerSubscribers(...$subscribers);
    }
}
