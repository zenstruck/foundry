<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Tests\Fixture\MigrationTests;

use DAMA\DoctrineTestBundle\DAMADoctrineTestBundle;
use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Doctrine\Bundle\MigrationsBundle\DoctrineMigrationsBundle;
use Psr\Log\NullLogger;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel;
use Zenstruck\Foundry\ORM\AbstractORMPersistenceStrategy;
use Zenstruck\Foundry\Tests\Fixture\Stories\GlobalInvokableService;
use Zenstruck\Foundry\Tests\Fixture\Stories\GlobalStory;
use Zenstruck\Foundry\ZenstruckFoundryBundle;

/**
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 */
final class TestMigrationKernel extends Kernel
{
    use MicroKernelTrait;

    public function registerBundles(): iterable
    {
        yield new FrameworkBundle();
        yield new DoctrineBundle();
        yield new DoctrineMigrationsBundle();

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
                    'mode' => AbstractORMPersistenceStrategy::RESET_MODE_MIGRATE,
                    AbstractORMPersistenceStrategy::RESET_MODE_MIGRATE => [
                        'configurations' => ($configFile = \getenv('WITH_MIGRATION_CONFIGURATION_FILE')) ? [$configFile] : [],
                    ],
                ],
            ],
        ]);

        if (!\getenv('WITH_MIGRATION_CONFIGURATION_FILE')) {
            $c->loadFromExtension('doctrine_migrations', include __DIR__.'/configs/migration-configuration.php');
        }

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
                ],
                'controller_resolver' => ['auto_mapping' => true],
            ],
        ]);

        // doctrine only handles different schema with postgres
        if (str_starts_with(\getenv('DATABASE_URL') ?: '', 'postgresql')) {
            $c->loadFromExtension('doctrine', [
                'orm' => [
                    'mappings' => [
                        'Migrate' => [
                            'is_bundle' => false,
                            'type' => 'attribute',
                            'dir' => '%kernel.project_dir%/tests/Fixture/MigrationTests/EntityInAnotherSchema',
                            'prefix' => 'Zenstruck\Foundry\Tests\Fixture\MigrationTests\EntityInAnotherSchema',
                            'alias' => 'Migrate',
                        ],
                    ],
                ],
            ]);
        }

        $c->register('logger', NullLogger::class);
        $c->register(GlobalInvokableService::class);
    }
}
