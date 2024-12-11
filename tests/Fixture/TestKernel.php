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

use DAMA\DoctrineTestBundle\DAMADoctrineTestBundle;
use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Doctrine\Bundle\MongoDBBundle\DoctrineMongoDBBundle;
use Psr\Log\NullLogger;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Bundle\MakerBundle\MakerBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;
use Zenstruck\Foundry\ORM\ResetDatabase\ResetDatabaseMode;
use Zenstruck\Foundry\Persistence\PersistenceManager;
use Zenstruck\Foundry\Tests\Fixture\DoctrineCascadeRelationship\ChangeCascadePersistOnLoadClassMetadataListener;
use Zenstruck\Foundry\Tests\Fixture\Factories\ArrayFactory;
use Zenstruck\Foundry\Tests\Fixture\Factories\Object1Factory;
use Zenstruck\Foundry\Tests\Fixture\Stories\GlobalInvokableService;
use Zenstruck\Foundry\Tests\Fixture\Stories\GlobalStory;
use Zenstruck\Foundry\Tests\Fixture\Stories\ServiceStory;
use Zenstruck\Foundry\ZenstruckFoundryBundle;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class TestKernel extends Kernel
{
    use MicroKernelTrait;

    public function registerBundles(): iterable
    {
        yield new FrameworkBundle();
        yield new MakerBundle();

        if (\getenv('DATABASE_URL')) {
            yield new DoctrineBundle();
        }

        if (\getenv('MONGO_URL')) {
            yield new DoctrineMongoDBBundle();
        }

        yield new ZenstruckFoundryBundle();

        if (\getenv('USE_DAMA_DOCTRINE_TEST_BUNDLE')) {
            yield new DAMADoctrineTestBundle();
        }
    }

    protected function configureContainer(ContainerBuilder $c, LoaderInterface $loader): void
    {
        $c->loadFromExtension('framework', [
            'http_method_override' => false,
            'secret' => 'S3CRET',
            'router' => ['utf8' => true],
            'test' => true,
        ]);

        $c->loadFromExtension('zenstruck_foundry', [
            'global_state' => [
                GlobalStory::class,
                GlobalInvokableService::class,
            ],
            'orm' => [
                'reset' => [
                    'mode' => ResetDatabaseMode::SCHEMA,
                ],
            ],
        ]);

        if (\getenv('DATABASE_URL')) {
            $c->loadFromExtension('doctrine', [
                'dbal' => ['url' => '%env(resolve:DATABASE_URL)%', 'use_savepoints' => true],
                'orm' => [
                    'auto_generate_proxy_classes' => true,
                    'auto_mapping' => true,
                    'mappings' => [
                        'Entity' => [
                            'is_bundle' => false,
                            'type' => 'attribute',
                            'dir' => '%kernel.project_dir%/tests/Fixture/Entity',
                            'prefix' => 'Zenstruck\Foundry\Tests\Fixture\Entity',
                            'alias' => 'Entity',
                        ],
                        'Model' => [
                            'is_bundle' => false,
                            'type' => 'attribute',
                            'dir' => '%kernel.project_dir%/tests/Fixture/Model',
                            'prefix' => 'Zenstruck\Foundry\Tests\Fixture\Model',
                            'alias' => 'Model',
                        ],

                        // postgres acts weirdly with multiple schemas
                        // @see https://github.com/doctrine/DoctrineBundle/issues/548
                        ...(\str_starts_with(\getenv('DATABASE_URL'), 'postgresql')
                            ? [
                                'EntityInAnotherSchema' => [
                                    'is_bundle' => false,
                                    'type' => 'attribute',
                                    'dir' => '%kernel.project_dir%/tests/Fixture/EntityInAnotherSchema',
                                    'prefix' => 'Zenstruck\Foundry\Tests\Fixture\EntityInAnotherSchema',
                                    'alias' => 'Migrate',
                                ],
                            ]
                            : []
                        ),
                    ],
                    'controller_resolver' => ['auto_mapping' => false],
                ],
            ]);

            $c->register(ChangeCascadePersistOnLoadClassMetadataListener::class)
                ->setAutowired(true)
                ->setAutoconfigured(true);
            $c->setAlias(PersistenceManager::class, '.zenstruck_foundry.persistence_manager')
                ->setPublic(true);
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
                            'Document' => [
                                'is_bundle' => false,
                                'type' => 'attribute',
                                'dir' => '%kernel.project_dir%/tests/Fixture/Document',
                                'prefix' => 'Zenstruck\Foundry\Tests\Fixture\Document',
                                'alias' => 'Document',
                            ],
                            'Model' => [
                                'is_bundle' => false,
                                'type' => 'attribute',
                                'dir' => '%kernel.project_dir%/tests/Fixture/Model',
                                'prefix' => 'Zenstruck\Foundry\Tests\Fixture\Model',
                                'alias' => 'Model',
                            ],
                        ],
                    ],
                ],
            ]);
        }

        $c->register('logger', NullLogger::class);
        $c->register(GlobalInvokableService::class);
        $c->register(ArrayFactory::class)->setAutowired(true)->setAutoconfigured(true);
        $c->register(Object1Factory::class)->setAutowired(true)->setAutoconfigured(true);
        $c->register(ServiceStory::class)->setAutowired(true)->setAutoconfigured(true);
    }

    protected function configureRoutes(RoutingConfigurator $routes): void
    {
    }
}
