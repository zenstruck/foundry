<?php

namespace Zenstruck\Foundry\Psalm;

use Psalm\Plugin\PluginEntryPointInterface;
use Psalm\Plugin\RegistrationInterface;
use SimpleXMLElement;

final class FoundryPlugin implements PluginEntryPointInterface
{
    public function __invoke(RegistrationInterface $registration, ?SimpleXMLElement $config = null): void
    {
        class_exists(FixProxyFactoryMethodsReturnType::class, true);
        $registration->registerHooksFromClass(FixProxyFactoryMethodsReturnType::class);
    }
}
