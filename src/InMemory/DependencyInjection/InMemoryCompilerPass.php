<?php

declare(strict_types=1);

namespace Zenstruck\Foundry\InMemory\DependencyInjection;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\ServiceLocatorTagPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Zenstruck\Foundry\InMemory\InMemoryFactoryRegistry;

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
            array_combine(
                array_map(
                    static function (array $tags) {
                        if (\count($tags) !== 1) {
                            throw new \LogicException('Cannot have multiple tags "foundry.in_memory.repository" on a service!');
                        }

                        return $tags[0]['class'] ?? throw new \LogicException('Invalid tag definition of "foundry.in_memory.repository".');
                    },
                    array_values($inMemoryRepositoriesServices)
                ),
                array_map(
                    static fn(string $inMemoryRepositoryId) => new Reference($inMemoryRepositoryId),
                    array_keys($inMemoryRepositoriesServices)
                ),
            )
        );

        // todo: should we check we only have a 1 repository per class?

        $container->findDefinition('.zenstruck_foundry.in_memory.repository_registry')
            ->setArgument('$inMemoryRepositories', $inMemoryRepositoriesLocator)
        ;
    }
}
