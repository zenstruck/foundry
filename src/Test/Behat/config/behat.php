<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Zenstruck\Foundry\Story\Event\StateAddedToStory;
use Zenstruck\Foundry\Test\Behat\FactoryShortNameResolver;
use Zenstruck\Foundry\Test\Behat\FoundryAssertionContext;
use Zenstruck\Foundry\Test\Behat\FoundryContext;
use Zenstruck\Foundry\Test\Behat\FoundryCreationContext;
use Zenstruck\Foundry\Test\Behat\FoundryPlaceholderContext;
use Zenstruck\Foundry\Test\Behat\ObjectRegistry;
use Zenstruck\Foundry\Test\Behat\TableParametersNormalizer;

return static function(ContainerConfigurator $container): void {
    $container->services()
        ->set('.zenstruck_foundry.behat.factory_resolver', FactoryShortNameResolver::class)
        ->args([
            tagged_iterator('foundry.factory'),
        ])
        ->public()

        ->set('.zenstruck_foundry.behat.object_registry', ObjectRegistry::class)
        ->args([
            service('.zenstruck_foundry.behat.factory_resolver'),
            service('.zenstruck_foundry.persistence_manager'),
        ])
        ->tag('kernel.event_listener', ['method' => 'storeAfterStateAddedToStory', 'event' => StateAddedToStory::class])
        ->public()

        ->set('.zenstruck_foundry.behat.table_normalizer', TableParametersNormalizer::class)
        ->args([
            service('.zenstruck_foundry.behat.factory_resolver'),
            service('.zenstruck_foundry.behat.object_registry'),
        ])

        // the step traits inject their (internal) dependencies through #[Required] setters, so the
        // contexts only need to be autowired — no explicit arguments, no public FQCN aliases
        ->set(FoundryContext::class, FoundryContext::class)
        ->public()
        ->autowire()
        ->autoconfigure()

        ->set(FoundryCreationContext::class, FoundryCreationContext::class)
        ->public()
        ->autowire()
        ->autoconfigure()

        ->set(FoundryAssertionContext::class, FoundryAssertionContext::class)
        ->public()
        ->autowire()
        ->autoconfigure()

        ->set(FoundryPlaceholderContext::class, FoundryPlaceholderContext::class)
        ->public()
        ->autowire()
        ->autoconfigure()
    ;
};
