<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Test\Behat\Exception;

use Behat\Behat\EventDispatcher\Event\BeforeFeatureTested;
use Behat\Behat\EventDispatcher\Event\BeforeScenarioTested;

final class InvalidResetDbTag extends \LogicException
{
    public function __construct(string $message, BeforeFeatureTested|BeforeScenarioTested $event)
    {
        $file = $event->getFeature()->getFile();

        if (!$file) {
            parent::__construct($message);

            return;
        }

        $suite = $event->getEnvironment()->getSuite();
        if ($suite->hasSetting('paths')) {
            foreach ($suite->getSetting('paths') as $path) {
                if (!$path) {
                    continue;
                }

                if (\str_contains($file, $path)) {
                    $file = \mb_substr($file, \mb_strpos($file, $path)); // @phpstan-ignore argument.type (strpos cannot be false if $path is contained in $file)
                    break;
                }
            }
        }

        $errorFileAndLine = match ($event::class) {
            BeforeFeatureTested::class => "{$file}:{$event->getFeature()->getLine()}",
            BeforeScenarioTested::class => "{$file}:{$event->getScenario()->getLine()}",
        };

        parent::__construct("{$message}\nAt {$errorFileAndLine}");
    }

    public static function bothTagsUsed(BeforeScenarioTested $event): self
    {
        return new self('Cannot use both "@resetDB" and "@noResetDB" tags at the same time.', $event);
    }

    public static function resetDbWithScenarioMode(BeforeFeatureTested|BeforeScenarioTested $event): self
    {
        return new self('Cannot use "@noResetDB" tag with database_reset_mode set as "manual".', $event);
    }

    public static function noResetDbWithManualMode(BeforeFeatureTested|BeforeScenarioTested $event): self
    {
        return new self('Cannot use "@noResetDB" tag with database_reset_mode set as "manual".', $event);
    }

    public static function resetDbOnFeatureWithFeatureMode(BeforeFeatureTested $event): self
    {
        return new self('Cannot use "@resetDB" tag on a feature with database_reset_mode set as "feature".', $event);
    }

    public static function resetDbOnScenarioWithScenarioMode(BeforeScenarioTested $event): self
    {
        return new self('Cannot use "@resetDB" tag on a scenario with database_reset_mode set as "scenario".', $event);
    }

    public static function noResetDbWithFeatureMode(BeforeFeatureTested|BeforeScenarioTested $event): self
    {
        return new self('Cannot use "@noResetDB" with database_reset_mode set as "feature".', $event);
    }
}
