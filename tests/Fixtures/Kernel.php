<?php

namespace Zenstruck\Foundry\Tests\Fixtures;

use DAMA\DoctrineTestBundle\DAMADoctrineTestBundle;
use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Doctrine\Bundle\MongoDBBundle\DoctrineMongoDBBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Bundle\MakerBundle\MakerBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Component\Routing\RouteCollectionBuilder;
use Zenstruck\Foundry\Tests\Fixtures\Factories\CategoryFactory;
use Zenstruck\Foundry\Tests\Fixtures\Factories\CategoryServiceFactory;
use Zenstruck\Foundry\Tests\Fixtures\Stories\ServiceStory;
use Zenstruck\Foundry\ZenstruckFoundryBundle;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    public function registerBundles(): iterable
    {
        yield new FrameworkBundle();

        if (\getenv('DATABASE_URL')) {
            yield new DoctrineBundle();
        }

        yield new MakerBundle();

        if (\getenv('USE_FOUNDRY_BUNDLE')) {
            yield new ZenstruckFoundryBundle();
        }

        if (\getenv('USE_DAMA_DOCTRINE_TEST_BUNDLE')) {
            yield new DAMADoctrineTestBundle();
        }

        if (\getenv('MONGO_URL')) {
            yield new DoctrineMongoDBBundle();
        }
    }

    protected function configureContainer(ContainerBuilder $c, LoaderInterface $loader): void
    {
        $c->register(Service::class);
        $c->register(ServiceStory::class)
            ->setAutoconfigured(true)
            ->setAutowired(true)
        ;
        $c->register(CategoryFactory::class)
            ->setAutoconfigured(true)
            ->setAutowired(true)
        ;
        $c->register(CategoryServiceFactory::class)
            ->setAutoconfigured(true)
            ->setAutowired(true)
        ;

        $c->loadFromExtension('framework', [
            'secret' => 'S3CRET',
            'test' => true,
        ]);

        if (\getenv('DATABASE_URL')) {
            $c->loadFromExtension(
                'doctrine',
                [
                    'dbal' => ['url' => '%env(resolve:DATABASE_URL)%'],
                    'orm' => [
                        'auto_generate_proxy_classes' => true,
                        'auto_mapping' => true,
                        'mappings' => [
                            'Test' => [
                                'is_bundle' => false,
                                'type' => 'annotation',
                                'dir' => '%kernel.project_dir%/tests/Fixtures/Entity',
                                'prefix' => 'Zenstruck\Foundry\Tests\Fixtures\Entity',
                                'alias' => 'Test',
                            ],
                        ],
                    ],
                ]
            );
        }

        if (\getenv('USE_FOUNDRY_BUNDLE')) {
            $c->loadFromExtension('zenstruck_foundry', [
                'auto_refresh_proxies' => false,
            ]);
        }

        if (\getenv('MONGO_URL')) {
            $c->loadFromExtension('doctrine_mongodb', [
                'connections' => [
                    'default' => ['server' => '%env(resolve:MONGO_URL)%'],
                ],
                'default_database' => 'mongo',
                'document_managers' => [
                    'default' => [
                        'auto_mapping' => true,
                        'mappings' => [
                            'Test' => [
                                'is_bundle' => false,
                                'type' => 'annotation',
                                'dir' => '%kernel.project_dir%/tests/Fixtures/Document',
                                'prefix' => 'Zenstruck\Foundry\Tests\Fixtures\Document',
                                'alias' => 'Test',
                            ],
                        ],
                    ],
                ],
            ]);
        }
    }

    protected function configureRoutes(RouteCollectionBuilder $routes): void
    {
        // noop
    }
}
