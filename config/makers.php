<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Zenstruck\Foundry\Maker\Factory\DoctrineScalarFieldsDefaultPropertiesGuesser;
use Zenstruck\Foundry\Maker\Factory\FactoryCandidatesClassesExtractor;
use Zenstruck\Foundry\Maker\Factory\FactoryClassMap;
use Zenstruck\Foundry\Maker\Factory\FactoryGenerator;
use Zenstruck\Foundry\Maker\Factory\LegacyORMDefaultPropertiesGuesser;
use Zenstruck\Foundry\Maker\Factory\NoPersistenceObjectsAutoCompleter;
use Zenstruck\Foundry\Maker\Factory\ObjectDefaultPropertiesGuesser;
use Zenstruck\Foundry\Maker\Factory\ODMDefaultPropertiesGuesser;
use Zenstruck\Foundry\Maker\Factory\ORMDefaultPropertiesGuesser;
use Zenstruck\Foundry\Maker\MakeFactory;
use Zenstruck\Foundry\Maker\MakeStory;
use Zenstruck\Foundry\Maker\NamespaceGuesser;
use Zenstruck\Foundry\ORM\DoctrineOrmVersionGuesser;

return static function (ContainerConfigurator $container): void {
    $container->services()
        ->set('.zenstruck_foundry.maker.story', MakeStory::class)
            ->args([
                service('.zenstruck_foundry.maker.namespace_guesser')
            ])
            ->tag('maker.command')

        ->set('.zenstruck_foundry.maker.factory', MakeFactory::class)
            ->args([
                service('kernel'),
                service('.zenstruck_foundry.maker.factory.generator'),
                service('.zenstruck_foundry.maker.factory.autoCompleter'),
                service('.zenstruck_foundry.maker.factory.candidate_classes_extractor'),
            ])
            ->tag('maker.command')

        ->set('.zenstruck_foundry.maker.factory.orm_default_properties_guesser', DoctrineOrmVersionGuesser::isOrmV3() ? ORMDefaultPropertiesGuesser::class : LegacyORMDefaultPropertiesGuesser::class)
            ->args([
                service('.zenstruck_foundry.persistence_manager')->nullOnInvalid(),
                service('.zenstruck_foundry.maker.factory.factory_class_map'),
                service('.zenstruck_foundry.maker.factory.generator'),
            ])
            ->tag('foundry.make_factory.default_properties_guesser')

        ->set('.zenstruck_foundry.maker.factory.odm_default_properties_guesser', ODMDefaultPropertiesGuesser::class)
            ->args([
                service('.zenstruck_foundry.persistence_manager')->nullOnInvalid(),
                service('.zenstruck_foundry.maker.factory.factory_class_map'),
                service('.zenstruck_foundry.maker.factory.generator'),
            ])
            ->tag('foundry.make_factory.default_properties_guesser')

        ->set('.zenstruck_foundry.maker.factory.doctrine_scalar_fields_default_properties_guesser', DoctrineScalarFieldsDefaultPropertiesGuesser::class)
            ->args([
                service('.zenstruck_foundry.persistence_manager')->nullOnInvalid(),
                service('.zenstruck_foundry.maker.factory.factory_class_map'),
                service('.zenstruck_foundry.maker.factory.generator'),
            ])
            ->tag('foundry.make_factory.default_properties_guesser')

        ->set('.zenstruck_foundry.maker.factory.object_default_properties_guesser', ObjectDefaultPropertiesGuesser::class)
            ->args([
                service('.zenstruck_foundry.maker.factory.factory_class_map'),
                service('.zenstruck_foundry.maker.factory.generator'),
            ])
            ->tag('foundry.make_factory.default_properties_guesser')

        ->set('.zenstruck_foundry.maker.factory.factory_class_map', FactoryClassMap::class)
            ->args([
                tagged_iterator('foundry.factory'),
            ])

        ->set('.zenstruck_foundry.maker.factory.generator', FactoryGenerator::class)
            ->args([
                service('.zenstruck_foundry.persistence_manager')->nullOnInvalid(),
                service('kernel'),
                tagged_iterator('foundry.make_factory.default_properties_guesser'),
                service('.zenstruck_foundry.maker.factory.factory_class_map'),
                service('.zenstruck_foundry.maker.namespace_guesser'),
            ])

        ->set('.zenstruck_foundry.maker.factory.autoCompleter', NoPersistenceObjectsAutoCompleter::class)
            ->args([
                param('kernel.project_dir')
            ])

        ->set('.zenstruck_foundry.maker.factory.candidate_classes_extractor', FactoryCandidatesClassesExtractor::class)
            ->args([
                service('.zenstruck_foundry.persistence_manager')->nullOnInvalid(),
                service('.zenstruck_foundry.maker.factory.factory_class_map'),
            ])

        ->set('.zenstruck_foundry.maker.namespace_guesser', NamespaceGuesser::class)
            ->args([
                service('.zenstruck_foundry.persistence_manager')->nullOnInvalid(),
            ])
    ;
};
