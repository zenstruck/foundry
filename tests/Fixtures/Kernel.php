<?php

namespace Zenstruck\Foundry\Tests\Fixtures;

use DAMA\DoctrineTestBundle\DAMADoctrineTestBundle;
use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Doctrine\Bundle\MigrationsBundle\DoctrineMigrationsBundle;
use Doctrine\Bundle\MongoDBBundle\DoctrineMongoDBBundle;
use FriendsOfBehat\SymfonyExtension\Bundle\FriendsOfBehatSymfonyExtensionBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Bundle\MakerBundle\MakerBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Component\Routing\RouteCollectionBuilder;
use Zenstruck\Foundry\Test\Behat\FactoriesContext;
use Zenstruck\Foundry\Test\ORMDatabaseResetter;
use Zenstruck\Foundry\Tests\Behat\TestContext;
use Zenstruck\Foundry\Tests\Fixtures\Factories\AddressFactory;
use Zenstruck\Foundry\Tests\Fixtures\Factories\CategoryFactory;
use Zenstruck\Foundry\Tests\Fixtures\Factories\CategoryServiceFactory;
use Zenstruck\Foundry\Tests\Fixtures\Factories\EntityForRelationsFactory;
use Zenstruck\Foundry\Tests\Fixtures\Factories\ODM\CommentFactory;
use Zenstruck\Foundry\Tests\Fixtures\Factories\ODM\UserFactory;
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

    public static function create(
        bool $enableDoctrine = true,
        string $ormResetMode = ORMDatabaseResetter::RESET_MODE_SCHEMA,
        array $factoriesRegistered = []
    ): self {
        $kernel = new self('test', true);

        $kernel->enableDoctrine = $enableDoctrine;
        $kernel->ormResetMode = $ormResetMode;
        $kernel->factoriesRegistered = $factoriesRegistered;

        return $kernel;
    }

    public function registerBundles(): iterable
    {
        yield new FrameworkBundle();

        if ($this->enableDoctrine && \getenv('USE_ORM')) {
            yield new DoctrineBundle();
        }

        yield new MakerBundle();

        if (\getenv('USE_FOUNDRY_BUNDLE')) {
            yield new ZenstruckFoundryBundle();
        }

        if (\getenv('USE_DAMA_DOCTRINE_TEST_BUNDLE')) {
            yield new DAMADoctrineTestBundle();
        }

        if (ORMDatabaseResetter::RESET_MODE_MIGRATE === $this->ormResetMode && $this->enableDoctrine && \getenv('USE_ORM')) {
            yield new DoctrineMigrationsBundle();
        }

        if ($this->enableDoctrine && \getenv('USE_ODM')) {
            yield new DoctrineMongoDBBundle();
        }

        yield new FriendsOfBehatSymfonyExtensionBundle();
    }

    public function getCacheDir(): string
    {
        return \sprintf(
            "{$this->getProjectDir()}/var/cache/test/%s",
            \md5(\json_encode([$this->enableDoctrine, $this->ormResetMode, $this->factoriesRegistered], \JSON_THROW_ON_ERROR))
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

        $c->register(TestContext::class)
            ->setAutoconfigured(true)
            ->setAutowired(true);

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

        if ($this->enableDoctrine && \getenv('USE_ORM')) {
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

            if ($this->enableDoctrine && \getenv('USE_ORM')) {
                $globalState[] = TagStory::class;
                $globalState[] = TagStoryAsInvokableService::class;

                $foundryConfig['database_resetter'] = ['orm' => ['reset_mode' => $this->ormResetMode]];
            }

            if ($this->enableDoctrine && \getenv('USE_ODM') && !\getenv('USE_DAMA_DOCTRINE_TEST_BUNDLE')) {
                $globalState[] = ODMTagStory::class;
                $globalState[] = ODMTagStoryAsAService::class;
            }

            $foundryConfig['global_state'] = $globalState;

            $c->loadFromExtension('zenstruck_foundry', $foundryConfig);
        }

        if (ORMDatabaseResetter::RESET_MODE_MIGRATE === $this->ormResetMode) {
            $c->loadFromExtension('doctrine_migrations', [
                'migrations_paths' => [
                    'Zenstruck\Foundry\Tests\Fixtures\Migrations' => '%kernel.project_dir%/tests/Fixtures/Migrations',
                ],
            ]);
        }

        if ($this->enableDoctrine && \getenv('USE_ODM')) {
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
