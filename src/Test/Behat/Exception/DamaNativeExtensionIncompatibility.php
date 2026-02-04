<?php

namespace Zenstruck\Foundry\Test\Behat\Exception;

final class DamaNativeExtensionIncompatibility extends \LogicException
{
    public static function withManualResetDbMode(): self
    {
        return new self(
            'Database reset mode "manual" is not supported the native Behat extension for "dama/doctrine-test-bundle" is enabled. Please enable Foundry\'s DAMA support with "enable_dama_support: true" and disable the native extension to enable manual database reset with DAMA support.'
        );
    }

    public static function withFeatureResetDbMode(): self
    {
        return new self(
            'Database reset mode "feature" is not supported the native Behat extension for "dama/doctrine-test-bundle" is enabled. Please enable Foundry\'s DAMA support with "enable_dama_support: true" and disable the native extension to enable automatic database reset at feature level with DAMA support.'
        );
    }

    public static function withFoundryDamaSupport(): self
    {
        return new self('Foundry\'s Dama support cannot be enabled when the native Behat extension for "dama/doctrine-test-bundle" is enabled.');
    }

    public static function withNoResetDbTag(): self
    {
        return new self('Cannot use "@noResetDB" with native Behat extension for "dama/doctrine-test-bundle".');
    }
}
