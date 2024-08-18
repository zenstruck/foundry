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

namespace Zenstruck\Foundry\InMemory\DependencyInjection;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\ServiceLocatorTagPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Zenstruck\Foundry\InMemory\InMemoryRepository;

/**
 * @internal
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 */
final class InMemoryCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        // create a service locator with all "in memory" repositories, indexed by target class
        $inMemoryRepositoriesServices = $container->findTaggedServiceIds('foundry.in_memory.repository');
        $inMemoryRepositoriesLocator = ServiceLocatorTagPass::register(
            $container,
            \array_combine(
                \array_map(
                    static function(string $serviceId) use ($container) {
                        /** @var class-string<InMemoryRepository<object>> $inMemoryRepositoryClass */
                        $inMemoryRepositoryClass = $container->getDefinition($serviceId)->getClass() ?? throw new \LogicException("Service \"{$serviceId}\" should have a class.");

                        return $inMemoryRepositoryClass::_class();
                    },
                    \array_keys($inMemoryRepositoriesServices)
                ),
                \array_map(
                    static fn(string $inMemoryRepositoryId) => new Reference($inMemoryRepositoryId),
                    \array_keys($inMemoryRepositoriesServices)
                ),
            )
        );

        $container->findDefinition('.zenstruck_foundry.in_memory.repository_registry')
            ->setArgument('$inMemoryRepositories', $inMemoryRepositoriesLocator)
        ;
    }
}
