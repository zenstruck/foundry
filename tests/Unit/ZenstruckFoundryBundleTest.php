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

namespace Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\Config\Definition\Loader\DefinitionFileLoader;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Config\FileLocator;
use Zenstruck\Foundry\ORM\ResetDatabase\ResetDatabaseMode;
use Zenstruck\Foundry\ZenstruckFoundryBundle;

final class ZenstruckFoundryBundleTest extends TestCase
{
    private function buildConfiguration(array $config = []): array
    {
        $treeBuilder = new TreeBuilder('zenstruck_foundry');
        $definitionLoader = new DefinitionFileLoader($treeBuilder, new FileLocator());
        $configurator = new DefinitionConfigurator($treeBuilder, $definitionLoader, __DIR__,'');

        (new ZenstruckFoundryBundle())->configure($configurator);

        return (new Processor())->process($treeBuilder->buildTree(), $config);
    }

    /**
     * @test
     */
    #[Test]
    public function configuration_default_values(): void
    {
        self::assertSame([
            'auto_refresh_proxies' => null,
            'faker' => [
                'locale' => null,
                'seed' => null,
                'service' => null,
            ],
            'instantiator' => [
                'use_constructor' => true,
                'allow_extra_attributes' => false,
                'always_force_properties' => false,
                'service' => null,
            ],
            'global_state' => [],
            'orm' => [
                'auto_persist' => true,
                'reset' => [
                    'connections' => ['default'],
                    'entity_managers' => ['default'],
                    'mode' => ResetDatabaseMode::SCHEMA,
                    'migrations' => [
                        'configurations' => [],
                    ]
                ],
            ],
            'mongo' => [
                'auto_persist' => true,
                'reset' => [
                    'document_managers' => ['default'],
                ],
            ],
            'make_factory' => [
                'default_namespace' => 'Factory',
                'add_hints' => true,
            ],
            'make_story' => [
                'default_namespace' => 'Story',
            ]
        ], $this->buildConfiguration());
    }
}
