<?php

namespace Zenstruck\Foundry;

use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Zenstruck\Foundry\Bundle\DependencyInjection\ZenstruckFoundryExtension;

/**
 * Must be at src root to be auto-configured by Symfony Flex.
 *
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ZenstruckFoundryBundle extends Bundle
{
    public function boot(): void
    {
        if (!Factory::isBooted()) {
            Factory::boot($this->container->get(Configuration::class));
        }
    }

    protected function createContainerExtension(): ?ExtensionInterface
    {
        return new ZenstruckFoundryExtension();
    }
}
