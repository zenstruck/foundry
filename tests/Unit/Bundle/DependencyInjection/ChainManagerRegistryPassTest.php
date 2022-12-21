<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Tests\Unit\Bundle\DependencyInjection;

use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractCompilerPassTestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Zenstruck\Foundry\Bundle\DependencyInjection\ChainManagerRegistryPass;

class ChainManagerRegistryPassTest extends AbstractCompilerPassTestCase
{
    /**
     * @test
     */
    public function add_both_odm_and_orm_if_present(): void
    {
        $this->setDefinition('.zenstruck_foundry.chain_manager_registry', new Definition());

        $this->setDefinition('doctrine', new Definition());
        $this->setDefinition('doctrine_mongodb', new Definition());

        $this->compile();

        $this->assertContainerBuilderHasServiceDefinitionWithArgument(
            '.zenstruck_foundry.chain_manager_registry',
            '$managerRegistries',
            [new Reference('doctrine'), new Reference('doctrine_mongodb')]
        );
    }

    /**
     * @test
     */
    public function only_add_orm(): void
    {
        $this->setDefinition('.zenstruck_foundry.chain_manager_registry', new Definition());

        $this->setDefinition('doctrine', new Definition());

        $this->compile();

        $this->assertContainerBuilderHasServiceDefinitionWithArgument(
            '.zenstruck_foundry.chain_manager_registry',
            '$managerRegistries',
            [new Reference('doctrine')]
        );
    }

    protected function registerCompilerPass(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new ChainManagerRegistryPass());
    }
}
