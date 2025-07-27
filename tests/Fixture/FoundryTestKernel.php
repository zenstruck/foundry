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
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel;
use Zenstruck\Foundry\Persistence\PersistenceManager;
use Zenstruck\Foundry\Tests\Fixture\DoctrineCascadeRelationship\ChangeCascadePersistOnLoadClassMetadataListener;
use Zenstruck\Foundry\ZenstruckFoundryBundle;

/**
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 */
abstract class FoundryTestKernel extends Kernel
{
    use MicroKernelTrait;

    public function registerBundles(): iterable
    {
        yield new FrameworkBundle();

        if (self::hasORM()) {
            yield new DoctrineBundle();
        }

        if (self::hasMongo()) {
            yield new DoctrineMongoDBBundle();
        }

        yield new ZenstruckFoundryBundle();

        if (self::usesDamaDoctrineTestBundle()) {
            yield new DAMADoctrineTestBundle();
        }
    }

    public static function hasORM(): bool
    {
        return (bool) \getenv('DATABASE_URL');
    }

    public static function hasMongo(): bool
    {
        return (bool) \getenv('MONGO_URL');
    }

    public static function usesMigrations(): bool
    {
        return 'migrate' === \getenv('DATABASE_RESET_MODE');
    }

    public static function usesDamaDoctrineTestBundle(): bool
    {
        return (bool) \getenv('USE_DAMA_DOCTRINE_TEST_BUNDLE');
    }

    public static function usePHP84LazyObjects(): bool
    {
        return \PHP_VERSION_ID >= 80400 && \getenv('USE_PHP_84_LAZY_OBJECTS');
    }

    protected function configureContainer(ContainerBuilder $c, LoaderInterface $loader): void
    {
        $frameworkConfiguration = [
            'http_method_override' => false,
            'secret' => 'S3CRET',
            'router' => ['utf8' => true],
            'test' => true,
            'uid' => ['default_uuid_version' => 7, 'time_based_uuid_version' => 7],
        ];

        if (\str_starts_with(self::VERSION, '6.4')) {
            // prevent a deprecation notice in Symfony 6.4
            $frameworkConfiguration['handle_all_throwables'] = true;
        }

        if (\str_starts_with(self::VERSION, '7.3')) {
            // prevent a deprecation notice in Symfony 7.3
            $frameworkConfiguration['property_info']['with_constructor_extractor'] = true;
        }

        $c->loadFromExtension('framework', $frameworkConfiguration);

        if (self::hasORM()) {
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
                        ...(\str_starts_with(\getenv('DATABASE_URL') ?: '', 'postgresql')
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

        if (self::hasMongo()) {
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
    }
}
