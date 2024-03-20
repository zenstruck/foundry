<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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
use Zenstruck\Foundry\Test\ORMDatabaseResetter;
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

    private bool $enableDoctrine = true;

    private string $ormResetMode = ORMDatabaseResetter::RESET_MODE_SCHEMA;

    private array $factoriesRegistered = [];
    private ?string $defaultMakeFactoryNamespace = null;

    public static function create(
        bool $enableDoctrine = true,
        string $ormResetMode = ORMDatabaseResetter::RESET_MODE_SCHEMA,
        array $factoriesRegistered = [],
        ?string $defaultMakeFactoryNamespace = null,
    ): self {
        $kernel = new self('test', true);

        $kernel->enableDoctrine = $enableDoctrine;
        $kernel->ormResetMode = $ormResetMode;
        $kernel->factoriesRegistered = $factoriesRegistered;
        $kernel->defaultMakeFactoryNamespace = $defaultMakeFactoryNamespace;

        return $kernel;
    }

    public function registerBundles(): iterable
    {
        yield new FrameworkBundle();

        if ($this->enableDoctrine && \getenv('DATABASE_URL')) {
            yield new DoctrineBundle();
        }

        yield new MakerBundle();

        if (\getenv('USE_FOUNDRY_BUNDLE')) {
            yield new ZenstruckFoundryBundle();
        }

        if (\getenv('USE_DAMA_DOCTRINE_TEST_BUNDLE')) {
            yield new DAMADoctrineTestBundle();
        }

        if ($this->withMigrations()) {
            yield new DoctrineMigrationsBundle();
        }

        if ($this->enableDoctrine && \getenv('MONGO_URL')) {
            yield new DoctrineMongoDBBundle();
        }
    }

    public function getCacheDir(): string
    {
        return \sprintf(
            "{$this->getProjectDir()}/var/cache/test/%s",
            \md5(
                \json_encode(
                    [
                        $this->enableDoctrine,
                        $this->ormResetMode,
                        $this->factoriesRegistered,
                        $this->defaultMakeFactoryNamespace,
                        $this->withMigrations(),
                    ],
                    \JSON_THROW_ON_ERROR,
                ),
            ),
        );
    }

    protected function configureContainer(ContainerBuilder $c, LoaderInterface $loader): void
    {
        $c->register(Service::class);
        $c->register(TagStoryAsInvokableService::class);
        $c->register(ServiceStory::class)
            ->setAutoconfigured(true)
            ->setAutowired(true)
        ;
        $c->register(CustomFakerProvider::class)->addTag('foundry.faker_provider');

        foreach ($this->factoriesRegistered as $factory) {
            $c->register($factory)
                ->setAutoconfigured(true)
                ->setAutowired(true)
            ;
        }

        $c->loadFromExtension('framework', [
            'secret' => 'S3CRET',
            'test' => true,
        ]);

        if ($this->enableDoctrine && \getenv('DATABASE_URL')) {
            $mappings = [
                'Test' => [
                    'is_bundle' => false,
                    'type' => 'attribute',
                    'dir' => '%kernel.project_dir%/tests/Fixtures/Entity',
                    'prefix' => 'Zenstruck\Foundry\Tests\Fixtures\Entity',
                    'alias' => 'Test',
                ],
            ];

            if (\PHP_VERSION_ID >= 80100) {
                $mappings['Test8.1'] = [
                    'is_bundle' => false,
                    'type' => 'attribute',
                    'dir' => '%kernel.project_dir%/tests/Fixtures/PHP81',
                    'prefix' => 'Zenstruck\Foundry\Tests\Fixtures\PHP81',
                    'alias' => 'TestPHP81',
                ];
            }

            $c->loadFromExtension(
                'doctrine',
                [
                    'dbal' => [
                        'url' => '%env(resolve:DATABASE_URL)%',
                        'use_savepoints' => true,
                        'schema_filter' => '~^(?!(doctrine_migration_versions)$)~',
                    ],
                    'orm' => [
                        'auto_generate_proxy_classes' => true,
                        'auto_mapping' => true,
                        'mappings' => $mappings,
                    ],
                ],
            );
        }

        if (\getenv('USE_FOUNDRY_BUNDLE')) {
            $foundryConfig = ['auto_refresh_proxies' => false];
            if ($this->defaultMakeFactoryNamespace) {
                $foundryConfig['make_factory'] = ['default_namespace' => $this->defaultMakeFactoryNamespace];
            }
            $globalState = [];

            if ($this->enableDoctrine && \getenv('DATABASE_URL')) {
                $globalState[] = TagStory::class;
                $globalState[] = TagStoryAsInvokableService::class;

                $foundryConfig['database_resetter'] = ['orm' => ['reset_mode' => $this->ormResetMode]];
            }

            if ($this->enableDoctrine && \getenv('MONGO_URL') && !\getenv('USE_DAMA_DOCTRINE_TEST_BUNDLE')) {
                $globalState[] = ODMTagStory::class;
                $globalState[] = ODMTagStoryAsAService::class;
                $c->register(ODMTagStoryAsAService::class)->addTag('foundry.story');
            }

            $foundryConfig['global_state'] = $globalState;

            $c->loadFromExtension('zenstruck_foundry', $foundryConfig);
        }

        if ($this->withMigrations()) {
            $c->loadFromExtension('doctrine_migrations', [
                'migrations_paths' => [
                    'Zenstruck\Foundry\Tests\Fixtures\Migrations' => '%kernel.project_dir%/tests/Fixtures/Migrations',
                ],
            ]);
        }

        if ($this->enableDoctrine && \getenv('MONGO_URL')) {
            $mappings = [
                'Test' => [
                    'is_bundle' => false,
                    'type' => 'attribute',
                    'dir' => '%kernel.project_dir%/tests/Fixtures/Document',
                    'prefix' => 'Zenstruck\Foundry\Tests\Fixtures\Document',
                    'alias' => 'Test',
                ],
            ];

            if (\PHP_VERSION_ID >= 80100) {
                $mappings['Test8.1'] = [
                    'is_bundle' => false,
                    'type' => 'attribute',
                    'dir' => '%kernel.project_dir%/tests/Fixtures/PHP81',
                    'prefix' => 'Zenstruck\Foundry\Tests\Fixtures\PHP81',
                    'alias' => 'TestPHP81',
                ];
            }

            $c->loadFromExtension('doctrine_mongodb', [
                'connections' => [
                    'default' => ['server' => '%env(resolve:MONGO_URL)%'],
                ],
                'default_database' => 'mongo',
                'document_managers' => [
                    'default' => [
                        'auto_mapping' => true,
                        'mappings' => $mappings,
                    ],
                ],
            ]);
        }
    }

    private function withMigrations(): bool
    {
        return $this->enableDoctrine && \getenv('DATABASE_URL') && \getenv('TEST_MIGRATIONS');
    }
}
