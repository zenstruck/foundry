<?php

namespace Zenstruck\Foundry\Tests\Fixture\Behat;

use FriendsOfBehat\SymfonyExtension\Bundle\FriendsOfBehatSymfonyExtensionBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\Reference;
use Zenstruck\Foundry\ORM\ResetDatabase\ResetDatabaseMode;
use Zenstruck\Foundry\Tests\Fixture\App\Controller\HelloWorldController;
use Zenstruck\Foundry\Tests\Fixture\App\Controller\UpdateGenericModel;
use Zenstruck\Foundry\Tests\Fixture\FoundryTestKernel;

class BehatTestKernel extends FoundryTestKernel
{
    public function registerBundles(): iterable
    {
        yield from parent::registerBundles();

        yield new FriendsOfBehatSymfonyExtensionBundle();
    }

    protected function configureContainer(ContainerConfigurator $configurator, LoaderInterface $loader, ContainerBuilder $c): void
    {
        parent::configureContainer($configurator, $loader, $c);

        $c->loadFromExtension('zenstruck_foundry', [
            'persistence' => ['flush_once' => true],
            'enable_auto_refresh_with_lazy_objects' => self::usePHP84LazyObjects(),
            'orm' => [
                'reset' => [
                    'mode' => ResetDatabaseMode::SCHEMA,
                ],
            ],
        ]);

        $c->register(HelloWorldController::class)->setAutowired(true)->setAutoconfigured(true)->addTag('controller.service_arguments');
        $c->register(UpdateGenericModel::class)->setAutowired(true)->setAutoconfigured(true)->addTag('controller.service_arguments');
        $c->register(ResetDisabledTestContext::class)->setAutowired(true)->setAutoconfigured(true);
        $c->register(TestFoundryContext::class)
            ->setAutowired(true)
            ->setAutoconfigured(true)
            ->setArguments([new Reference('.zenstruck_foundry.behat.factory_resolver'), new Reference('.zenstruck_foundry.behat.object_registry')])
        ;

        $configurator->services()
            ->load('Zenstruck\\Foundry\\Tests\\Fixture\\Behat\\Factories\\', __DIR__.'/Factories')
            ->autowire()
            ->autoconfigure();

        $configurator->services()
            ->load('Zenstruck\\Foundry\\Tests\\Fixture\\Factories\\', __DIR__.'/../Factories')
            ->autowire()
            ->autoconfigure();

        $configurator->services()
            ->load('Zenstruck\\Foundry\\Tests\\Fixture\\Behat\\Stories\\', __DIR__.'/Stories')
            ->autowire()
            ->autoconfigure();

        if (!self::runsWithBehat()) {
            $c->register('behat.service_container', \stdClass::class);
        }
    }

    private static function runsWithBehat(): bool
    {
        return str_contains($_SERVER['SCRIPT_NAME'], 'behat');
    }
}
