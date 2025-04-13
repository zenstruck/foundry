<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Faker;
use Zenstruck\Foundry\Configuration;
use Zenstruck\Foundry\FactoryRegistry;
use Zenstruck\Foundry\Object\Instantiator;
use Zenstruck\Foundry\StoryRegistry;

return static function (ContainerConfigurator $container): void {
    $container->services()
        ->set('.zenstruck_foundry.faker', Faker\Generator::class)
        ->factory([Faker\Factory::class, 'create'])

        ->set('.zenstruck_foundry.factory_registry', FactoryRegistry::class)
        ->args([tagged_iterator('foundry.factory')])

        ->set('.zenstruck_foundry.story_registry', StoryRegistry::class)
        ->args([
            tagged_iterator('foundry.story'),
            abstract_arg('global_stories'),
        ])

        ->set('.zenstruck_foundry.instantiator', Instantiator::class)
        ->factory([Instantiator::class, 'withConstructor'])

        ->set('.zenstruck_foundry.configuration', Configuration::class)
        ->args([
            service('.zenstruck_foundry.factory_registry'),
            service('.zenstruck_foundry.faker'),
            service('.zenstruck_foundry.instantiator'),
            service('.zenstruck_foundry.story_registry'),
            service('.zenstruck_foundry.persistence_manager')->nullOnInvalid(),
            service('event_dispatcher'),
            '%env(default:zenstruck_foundry.faker.seed:int:FOUNDRY_FAKER_SEED)%',
        ])
        ->public()
    ;
};
