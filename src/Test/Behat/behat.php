<?php

use Behat\Config\Config;
use Behat\Config\Extension;
use Behat\Config\Profile;
use Behat\Config\Suite;
use Behat\Config\TesterOptions;
use Behat\MinkExtension\Context\MinkContext;
use Behat\MinkExtension\ServiceContainer\MinkExtension;
use DAMA\DoctrineTestBundle\Behat\ServiceContainer\DoctrineExtension;
use FriendsOfBehat\SymfonyExtension\ServiceContainer\SymfonyExtension;
use Yceruto\BehatExtension\Extension\ExceptionExtension;
use Zenstruck\Foundry\Test\Behat\FoundryContext;
use Zenstruck\Foundry\Test\Behat\FoundryCreationContext;
use Zenstruck\Foundry\Test\Behat\FoundryExtension;
use Zenstruck\Foundry\Test\Behat\FoundryPlaceholderContext;
use Zenstruck\Foundry\Test\Behat\Tests\Fixture\CustomCountContext;
use Zenstruck\Foundry\Test\Behat\Tests\Fixture\OverridingFoundryContext;
use Zenstruck\Foundry\Test\Behat\Tests\Fixture\ResetDisabledTestContext;
use Zenstruck\Foundry\Test\Behat\Tests\Fixture\TestFoundryContext;

return (new Config())
    // raw settings: the GherkinOptions helper only exists since Behat 3.30, above our floor
    ->withProfile((new Profile('default', ['gherkin' => ['cache' => 'var/cache/gherkin']]))
        ->withTesterOptions((new TesterOptions())
            ->withStopOnFailure())
        ->withExtension(new Extension(ExceptionExtension::class))
        ->withExtension(new Extension(FoundryExtension::class, [
            'database_reset_mode' => 'scenario',
            'enable_dama_support' => true,
        ]))
        ->withExtension(new Extension(MinkExtension::class, [
            'sessions' => [
                'symfony' => [
                    'symfony' => null,
                ],
            ],
        ]))
        ->withExtension(new Extension(SymfonyExtension::class, [
            'bootstrap' => 'tests/bootstrap.php',
            'kernel' => [
                'class' => 'Zenstruck\Foundry\Test\Behat\Tests\Fixture\BehatTestKernel',
            ],
        ]))
        ->withSuite((new Suite('main'))
            ->withContexts(
                MinkContext::class,
                FoundryContext::class,
                TestFoundryContext::class
            )
            ->withPaths('features/main')))
    ->withProfile((new Profile('main-no-dama', ['suites' => ['main' => false]]))
        ->withExtension(new Extension(FoundryExtension::class, [
            'database_reset_mode' => 'scenario',
            'enable_dama_support' => false,
        ]))
        ->withSuite((new Suite('main-no-dama'))
            ->withContexts(
                MinkContext::class,
                FoundryContext::class,
                TestFoundryContext::class
            )
            ->withPaths('features/main')))
    ->withProfile((new Profile('main-native-dama', ['suites' => ['main' => false]]))
        ->withExtension(new Extension(DoctrineExtension::class))
        ->withExtension(new Extension(FoundryExtension::class, [
            'database_reset_mode' => 'scenario',
            'enable_dama_support' => false,
        ]))
        ->withSuite((new Suite('main-native-dama'))
            ->withContexts(
                MinkContext::class,
                FoundryContext::class,
                TestFoundryContext::class
            )
            ->withPaths('features/main')))
    ->withProfile((new Profile('reset-manual', ['suites' => ['main' => false]]))
        ->withExtension(new Extension(FoundryExtension::class, [
            'database_reset_mode' => 'manual',
            'enable_dama_support' => false,
        ]))
        ->withSuite((new Suite('reset-manual'))
            ->withContexts(
                MinkContext::class,
                FoundryContext::class,
                TestFoundryContext::class
            )
            ->withPaths('features/reset-manual')))
    ->withProfile((new Profile('reset-manual-dama', ['suites' => ['main' => false]]))
        ->withExtension(new Extension(FoundryExtension::class, [
            'database_reset_mode' => 'manual',
            'enable_dama_support' => true,
        ]))
        ->withSuite((new Suite('reset-manual-dama'))
            ->withContexts(
                MinkContext::class,
                FoundryContext::class,
                TestFoundryContext::class
            )
            ->withPaths('features/reset-manual')))
    ->withProfile((new Profile('reset-feature', ['suites' => ['main' => false]]))
        ->withExtension(new Extension(FoundryExtension::class, [
            'database_reset_mode' => 'feature',
            'enable_dama_support' => false,
        ]))
        ->withSuite((new Suite('reset-feature'))
            ->withContexts(
                MinkContext::class,
                FoundryContext::class,
                TestFoundryContext::class
            )
            ->withPaths('features/reset-feature')))
    ->withProfile((new Profile('reset-feature-dama', ['suites' => ['main' => false]]))
        ->withExtension(new Extension(FoundryExtension::class, [
            'database_reset_mode' => 'feature',
            'enable_dama_support' => true,
        ]))
        ->withSuite((new Suite('reset-feature-dama'))
            ->withContexts(
                MinkContext::class,
                FoundryContext::class,
                TestFoundryContext::class
            )
            ->withPaths('features/reset-feature')))
    ->withProfile((new Profile('reset-disabled', ['suites' => ['main' => false]]))
        ->withExtension(new Extension(FoundryExtension::class, [
            'database_reset_mode' => 'disabled',
            'enable_dama_support' => false,
        ]))
        ->withSuite((new Suite('reset-disabled'))
            ->withContexts(
                MinkContext::class,
                FoundryContext::class,
                TestFoundryContext::class,
                ResetDisabledTestContext::class
            )
            ->withPaths('features/reset-disabled')))
    ->withProfile((new Profile('override-steps', ['suites' => ['main' => false]]))
        ->withExtension(new Extension(FoundryExtension::class, [
            'database_reset_mode' => 'scenario',
            'enable_dama_support' => true,
        ]))
        ->withSuite((new Suite('override-steps'))
            ->withContexts(
                MinkContext::class,
                OverridingFoundryContext::class,
                TestFoundryContext::class
            )
            ->withPaths('features/override-steps')))
    ->withProfile((new Profile('granular-contexts', ['suites' => ['main' => false]]))
        ->withExtension(new Extension(FoundryExtension::class, [
            'database_reset_mode' => 'scenario',
            'enable_dama_support' => true,
        ]))
        ->withSuite((new Suite('granular-contexts'))
            ->withContexts(
                MinkContext::class,
                FoundryCreationContext::class,
                FoundryPlaceholderContext::class,
                TestFoundryContext::class,
                CustomCountContext::class
            )
            ->withPaths('features/granular-contexts')));
