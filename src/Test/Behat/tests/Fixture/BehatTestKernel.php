<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Test\Behat\Tests\Fixture;

use FriendsOfBehat\SymfonyExtension\Bundle\FriendsOfBehatSymfonyExtensionBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Zenstruck\Foundry\ORM\ResetDatabase\ResetDatabaseMode;
use Zenstruck\Foundry\Test\Behat\FoundryContext;
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
        $c->setDefinition(TestFoundryContext::class, new ChildDefinition(FoundryContext::class));

        $configurator->services()
            ->load('Zenstruck\\Foundry\\Test\\Behat\\Tests\\Fixture\\Factories\\', __DIR__.'/Factories')
            ->autowire()
            ->autoconfigure();

        $configurator->services()
            ->load('Zenstruck\\Foundry\\Tests\\Fixture\\Factories\\', __DIR__.'/../../../../../tests/Fixture/Factories')
            ->autowire()
            ->autoconfigure();

        $configurator->services()
            ->load('Zenstruck\\Foundry\\Test\\Behat\\Tests\\Fixture\\Stories\\', __DIR__.'/Stories')
            ->autowire()
            ->autoconfigure();
    }

    protected function baseFixturePath(): string
    {
        return '%kernel.project_dir%/../../../tests/Fixture';
    }
}
