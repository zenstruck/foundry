<?php

namespace Zenstruck\Foundry\Bundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Zenstruck\Foundry\Configuration;
use Zenstruck\Foundry\Factory;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ZenstruckFoundryBundle extends Bundle
{
    public function boot()
    {
        Factory::boot($this->container->get(Configuration::class));
    }
}
