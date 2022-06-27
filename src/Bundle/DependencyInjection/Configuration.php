<?php

namespace Zenstruck\Foundry\Bundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('zenstruck_foundry');

        $treeBuilder->getRootNode()
            ->children()
                ->booleanNode('auto_refresh_proxies')
                    ->info('Whether to auto-refresh proxies by default (https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#auto-refresh)')
                    ->defaultNull()
                ->end()
                ->arrayNode('faker')
                    ->addDefaultsIfNotSet()
                    ->info('Configure faker to be used by your factories.')
                    ->validate()
                        ->ifTrue(static function(array $v) {
                            return $v['locale'] && $v['service'];
                        })
                        ->thenInvalid('Cannot set faker locale when using custom service.')
                    ->end()
                    ->validate()
                        ->ifTrue(static function(array $v) {
                            return $v['seed'] && $v['service'];
                        })
                        ->thenInvalid('Cannot set faker seed when using custom service.')
                    ->end()
                    ->children()
                        ->scalarNode('locale')
                            ->defaultNull()
                            ->info('Change the default faker locale.')
                            ->example('fr_FR')
                        ->end()
                        ->integerNode('seed')
                            ->defaultNull()
                            ->info('Random number generator seed to produce the same fake values every run')
                            ->example(1234)
                        ->end()
                        ->scalarNode('service')
                            ->defaultNull()
                            ->info('Customize the faker service.')
                            ->example('my_faker')
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('instantiator')
                    ->addDefaultsIfNotSet()
                    ->info('Configure the default instantiator used by your factories.')
                    ->validate()
                        ->ifTrue(static function(array $v) {
                            return $v['service'] && $v['without_constructor'];
                        })
                        ->thenInvalid('Cannot set "without_constructor" when using custom service.')
                    ->end()
                    ->validate()
                        ->ifTrue(static function(array $v) {
                            return $v['service'] && $v['allow_extra_attributes'];
                        })
                        ->thenInvalid('Cannot set "allow_extra_attributes" when using custom service.')
                    ->end()
                    ->validate()
                        ->ifTrue(static function(array $v) {
                            return $v['service'] && $v['always_force_properties'];
                        })
                        ->thenInvalid('Cannot set "always_force_properties" when using custom service.')
                    ->end()
                    ->children()
                        ->booleanNode('without_constructor')
                            ->defaultFalse()
                            ->info('Whether or not to call an object\'s constructor during instantiation.')
                        ->end()
                        ->booleanNode('allow_extra_attributes')
                            ->defaultFalse()
                            ->info('Whether or not to allow extra attributes.')
                        ->end()
                        ->booleanNode('always_force_properties')
                            ->defaultFalse()
                            ->info('Whether or not to skip setters and force set object properties (public/private/protected) directly.')
                        ->end()
                        ->scalarNode('service')
                            ->defaultNull()
                            ->info('Customize the instantiator service.')
                            ->example('my_instantiator')
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
