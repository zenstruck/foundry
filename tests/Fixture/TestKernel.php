<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Tests\Fixture;

use Symfony\Bundle\MakerBundle\MakerBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;
use Zenstruck\Foundry\ORM\ResetDatabase\ResetDatabaseMode;
use Zenstruck\Foundry\Tests\Fixture\App\Command\UpdateGenericModelCommand;
use Zenstruck\Foundry\Tests\Fixture\App\Controller\CreateContact;
use Zenstruck\Foundry\Tests\Fixture\App\Controller\DeleteGenericModel;
use Zenstruck\Foundry\Tests\Fixture\App\Controller\HelloWorld;
use Zenstruck\Foundry\Tests\Fixture\App\Controller\UpdateGenericModel;
use Zenstruck\Foundry\Tests\Fixture\Events\FoundryEventListener;
use Zenstruck\Foundry\Tests\Fixture\Factories\ArrayFactory;
use Zenstruck\Foundry\Tests\Fixture\Factories\Object1Factory;
use Zenstruck\Foundry\Tests\Fixture\InMemory\InMemoryAddressRepository;
use Zenstruck\Foundry\Tests\Fixture\InMemory\InMemoryContactRepository;
use Zenstruck\Foundry\Tests\Fixture\Stories\ServiceStory;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class TestKernel extends FoundryTestKernel
{
    public function registerBundles(): iterable
    {
        yield from parent::registerBundles();

        yield new MakerBundle();
    }

    protected function configureContainer(ContainerBuilder $c, LoaderInterface $loader): void
    {
        parent::configureContainer($c, $loader);

        $c->loadFromExtension('zenstruck_foundry', [
            'persistence' => ['flush_once' => true],
            'enable_auto_refresh_with_lazy_objects' => self::usePHP84LazyObjects(),
            'orm' => [
                'reset' => [
                    'mode' => ResetDatabaseMode::SCHEMA,
                ],
            ],
        ]);

        if ('dev' !== $this->getEnvironment()) {
            $loader->load(\sprintf('%s/config/%s.yaml', __DIR__, $this->getEnvironment()));
        }

        $c->register(ArrayFactory::class)->setAutowired(true)->setAutoconfigured(true);
        $c->register(Object1Factory::class)->setAutowired(true)->setAutoconfigured(true);
        $c->register(ServiceStory::class)->setAutowired(true)->setAutoconfigured(true);
        $c->register(InMemoryAddressRepository::class)->setAutowired(true)->setAutoconfigured(true);
        $c->register(InMemoryContactRepository::class)->setAutowired(true)->setAutoconfigured(true);

        $c->register(FoundryEventListener::class)->setAutowired(true)->setAutoconfigured(true);

        $c->register(DeleteGenericModel::class)->setAutowired(true)->setAutoconfigured(true)->addTag('controller.service_arguments');
        $c->register(UpdateGenericModel::class)->setAutowired(true)->setAutoconfigured(true)->addTag('controller.service_arguments');
        $c->register(CreateContact::class)->setAutowired(true)->setAutoconfigured(true)->addTag('controller.service_arguments');
        $c->register(HelloWorld::class)->setAutowired(true)->setAutoconfigured(true)->addTag('controller.service_arguments');
        $c->register(UpdateGenericModelCommand::class)->setAutowired(true)->setAutoconfigured(true);
    }

    protected function configureRoutes(RoutingConfigurator $routes): void
    {
        $routes->import(__DIR__.'/App/Controller/*.php', 'attribute');
    }
}
