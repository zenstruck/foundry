<?php

namespace Zenstruck\Foundry\Bundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Zenstruck\Foundry\Factory;
use Zenstruck\Foundry\Manager;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ZenstruckFoundryBundle extends Bundle
{
    public function boot()
    {
        Factory::boot($this->container->get(Manager::class));
    }
}
