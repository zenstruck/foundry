<?php

namespace Zenstruck\Foundry\Test\Behat\Exception;

final class InvalidResetDbTag extends \LogicException
{
    public static function bothTagsUsed(): self
    {
        return new self('Cannot use both "@resetDB" and "@noResetDB" tags at the same time.');
    }

    public static function resetDbWithScenarioMode(): self
    {
        return new self('Cannot use "@noResetDB" tag with database_reset_mode set as "manual".');
    }

    public static function noResetDbWithManualMode(): self
    {
        return new self('Cannot use "@noResetDB" tag with database_reset_mode set as "manual".');
    }

    public static function resetDbOnFeatureWithFeatureMode(): self
    {
        return new self('Cannot use "@resetDB" tag on a feature with database_reset_mode set as "feature".');
    }

    public static function resetDbOnScenarioWithScenarioMode(): self
    {
        return new self('Cannot use "@resetDB" tag on a scenario with database_reset_mode set as "scenario".');
    }

    public static function noResetDbWithFeatureMode(): self
    {
        return new self('Cannot use "@noResetDB" with database_reset_mode set as "feature".');
    }
}
