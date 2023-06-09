<?php

declare(strict_types=1);

namespace Zenstruck\Foundry\Psalm;

use Psalm\Plugin\PluginEntryPointInterface;
use Psalm\Plugin\RegistrationInterface;
use SimpleXMLElement;

final class FoundryPlugin implements PluginEntryPointInterface
{
    public function __invoke(RegistrationInterface $psalm, ?SimpleXMLElement $config = null): void
    {
        class_exists(RemoveFactoryPhpDocMethods::class, true);
        $psalm->registerHooksFromClass(RemoveFactoryPhpDocMethods::class);

        class_exists(FixFactoryMethodsReturnType::class, true);
        $psalm->registerHooksFromClass(FixFactoryMethodsReturnType::class);

        class_exists(FixAnonymousFunctions::class, true);
        $psalm->registerHooksFromClass(FixAnonymousFunctions::class);
    }
}
