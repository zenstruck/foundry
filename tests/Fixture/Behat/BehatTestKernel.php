<?php

namespace Zenstruck\Foundry\Tests\Fixture\Behat;

use FriendsOfBehat\SymfonyExtension\Bundle\FriendsOfBehatSymfonyExtensionBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;
use Zenstruck\Foundry\Tests\Fixture\App\Controller\HelloWorldController;
use Zenstruck\Foundry\Tests\Fixture\FoundryTestKernel;

final class BehatTestKernel extends FoundryTestKernel
{
    public function registerBundles(): iterable
    {
        yield from parent::registerBundles();

        yield new FriendsOfBehatSymfonyExtensionBundle();
    }

    protected function configureContainer(ContainerBuilder $c, LoaderInterface $loader): void
    {
        parent::configureContainer($c, $loader);

        $c->register(HelloWorldController::class)->setAutowired(true)->setAutoconfigured(true)->addTag('controller.service_arguments');
        $c->register(TestContext::class)->setAutowired(true)->setAutoconfigured(true);
    }
}
