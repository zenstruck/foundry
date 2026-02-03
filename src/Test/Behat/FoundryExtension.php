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

namespace Zenstruck\Foundry\Test\Behat;

use Behat\Behat\EventDispatcher\ServiceContainer\EventDispatcherExtension;
use Behat\Testwork\Call\ServiceContainer\CallExtension;
use Behat\Testwork\ServiceContainer\Extension;
use Behat\Testwork\ServiceContainer\ExtensionManager;
use DAMA\DoctrineTestBundle\Behat\ServiceContainer\DoctrineExtension;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Zenstruck\Foundry\Test\Behat\Exception\DamaNativeExtensionIncompatibility;
use Zenstruck\Foundry\Test\Behat\Listener\BootConfigurationListener;
use Zenstruck\Foundry\Test\Behat\Listener\DatabaseResetListener;
use Zenstruck\Foundry\Test\Behat\Listener\LoadFixturesListener;

final class FoundryExtension implements Extension
{
    public function process(ContainerBuilder $container): void
    {
    }

    public function getConfigKey(): string
    {
        return 'zenstruck_foundry';
    }

    public function initialize(ExtensionManager $extensionManager): void
    {
    }

    public function configure(ArrayNodeDefinition $builder): void
    {
        $builder
            ->children()
                ->enumNode('database_reset_mode')
                    ->values(array_map(static fn(DatabaseResetMode $mode) => $mode->value, DatabaseResetMode::cases()))
                    ->defaultValue(DatabaseResetMode::MANUAL->value)
                ->end()
                ->booleanNode('enable_dama_support')
                    ->defaultFalse()
                ->end()
            ->end()
            ->validate()
                ->ifTrue(static fn(array $v): bool => $v['enable_dama_support'] && DatabaseResetMode::DISABLED->value === $v['database_reset_mode'])
                ->thenInvalid('Foundry\'s DAMA support cannot be enabled when database reset is disabled.')
            ->end();
    }

    public function load(ContainerBuilder $container, array $config): void
    {
        $container->register('.zenstruck_foundry.behat.listener.boot_configuration', BootConfigurationListener::class)
            ->setArgument('$symfonyKernel', new Reference('fob_symfony.kernel'))
            ->addTag(EventDispatcherExtension::SUBSCRIBER_TAG);

        $container->register('.zenstruck_foundry.behat.listener.load_fixture', LoadFixturesListener::class)
            ->setArgument('$symfonyKernel', new Reference('fob_symfony.kernel'))
            ->addTag(EventDispatcherExtension::SUBSCRIBER_TAG);

        $container->register(FoundryCallFilter::class)
            ->setArgument('$symfonyKernel', new Reference('fob_symfony.kernel'))
            ->addTag(CallExtension::CALL_FILTER_TAG);

        $databaseResetMode = DatabaseResetMode::from($config['database_reset_mode']);

        if ($databaseResetMode === DatabaseResetMode::DISABLED) {
            return;
        }

        if ($this->damaNativeExtensionIsEnabled($container)) {
            if ($config['enable_dama_support']) {
                throw DamaNativeExtensionIncompatibility::withFoundryDamaSupport();
            }

            if ($databaseResetMode === DatabaseResetMode::FEATURE) {
                throw DamaNativeExtensionIncompatibility::withFeatureResetDbMode();
            }

            if ($databaseResetMode === DatabaseResetMode::MANUAL) {
                throw DamaNativeExtensionIncompatibility::withManualResetDbMode();
            }
        }

        $container->register('.zenstruck_foundry.behat.listener.database_reset', DatabaseResetListener::class)
            ->setArgument('$symfonyKernel', new Reference('fob_symfony.kernel'))
            ->setArgument('$resetMode', $databaseResetMode)
            ->setArgument('$damaSupportEnabled', $config['enable_dama_support'])
            ->setArgument('$damaNativeExtensionIsEnabled', $this->damaNativeExtensionIsEnabled($container))
            ->addTag(EventDispatcherExtension::SUBSCRIBER_TAG);
    }

    private function damaNativeExtensionIsEnabled(ContainerBuilder $container): bool
    {
        return in_array(DoctrineExtension::class, (array) $container->getParameter('extensions'), true);
    }
}
