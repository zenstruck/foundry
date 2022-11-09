<?php

namespace Zenstruck\Foundry\Tests\Fixtures;

use DAMA\DoctrineTestBundle\DAMADoctrineTestBundle;
use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Doctrine\Bundle\MigrationsBundle\DoctrineMigrationsBundle;
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
use Zenstruck\Foundry\Tests\Fixtures\Stories\ODMTagStory;
use Zenstruck\Foundry\Tests\Fixtures\Stories\ODMTagStoryAsAService;
use Zenstruck\Foundry\Tests\Fixtures\Stories\ServiceStory;
use Zenstruck\Foundry\Tests\Fixtures\Stories\TagStory;
use Zenstruck\Foundry\Tests\Fixtures\Stories\TagStoryAsInvokableService;
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

        if (\getenv('USE_MIGRATIONS')) {
            yield new DoctrineMigrationsBundle();
        }

        if (\getenv('MONGO_URL')) {
            yield new DoctrineMongoDBBundle();
        }
    }

    protected function configureContainer(ContainerBuilder $c, LoaderInterface $loader): void
    {
        $c->register(Service::class);
        $c->register(TagStoryAsInvokableService::class);
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
            $foundryConfig = ['auto_refresh_proxies' => false];
            $globalState = [];

            if (\getenv('DATABASE_URL')) {
                $globalState[] = TagStory::class;
                $globalState[] = TagStoryAsInvokableService::class;

                if (\getenv('USE_MIGRATIONS')) {
                    $foundryConfig['database_resetter'] = ['orm' => ['reset_mode' => 'migrate']];
                }
            }

            if (\getenv('MONGO_URL') && !\getenv('USE_DAMA_DOCTRINE_TEST_BUNDLE')) {
                $globalState[] = ODMTagStory::class;
                $globalState[] = ODMTagStoryAsAService::class;
            }

            $foundryConfig['global_state'] = $globalState;

            $c->loadFromExtension('zenstruck_foundry', $foundryConfig);
        }

        if (\getenv('USE_MIGRATIONS')) {
            $c->loadFromExtension('doctrine_migrations', [
                'migrations_paths' => [
                    'Zenstruck\Foundry\Tests\Fixtures\Migrations' => '%kernel.project_dir%/tests/Fixtures/Migrations',
                ],
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
