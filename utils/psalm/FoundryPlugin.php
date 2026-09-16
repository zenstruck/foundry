<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Psalm;

use Psalm\Plugin\PluginEntryPointInterface;
use Psalm\Plugin\RegistrationInterface;

final class FoundryPlugin implements PluginEntryPointInterface
{
    public function __invoke(RegistrationInterface $registration, ?\SimpleXMLElement $config = null): void
    {
        \class_exists(FixProxyFactoryMethodsReturnType::class, true);
        $registration->registerHooksFromClass(FixProxyFactoryMethodsReturnType::class);

        \class_exists(FixCreateHelpersReturnType::class, true);
        $registration->registerHooksFromClass(FixCreateHelpersReturnType::class);
    }
}
