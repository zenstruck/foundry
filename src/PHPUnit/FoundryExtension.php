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
    public const MIN_PHPUNIT_VERSION = '11.4';

    public function bootstrap(
        TextUI\Configuration\Configuration $configuration,
        Runner\Extension\Facade $facade,
        Runner\Extension\ParameterCollection $parameters,
    ): void {
        if (!ConstraintRequirement::from(self::MIN_PHPUNIT_VERSION)->isSatisfiedBy(Runner\Version::id())) {
            throw new \LogicException(\sprintf('Your PHPUnit version (%s) is not compatible with the minimum version (%s) needed to use this extension.', Runner\Version::id(), self::MIN_PHPUNIT_VERSION));
        }

        // shutdown Foundry if for some reason it has been booted before
        if (Configuration::isBooted()) {
            Configuration::shutdown();
        }

        $facade->registerSubscribers(
            new BootFoundryOnDataProviderMethodCalled(),
            new ShutdownFoundryOnDataProviderMethodFinished(),
        );
    }
}
