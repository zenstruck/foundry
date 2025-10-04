<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Tests\Fixture\ResetDatabase;

use Doctrine\Bundle\MigrationsBundle\DoctrineMigrationsBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Zenstruck\Foundry\ORM\ResetDatabase\ResetDatabaseMode;
use Zenstruck\Foundry\Tests\Fixture\FoundryTestKernel;
use Zenstruck\Foundry\Tests\Fixture\Stories\GlobalInvokableService;
use Zenstruck\Foundry\Tests\Fixture\Stories\GlobalStory;

/**
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 */
final class ResetDatabaseTestKernel extends FoundryTestKernel
{
    public function registerBundles(): iterable
    {
        yield from parent::registerBundles();

        if (self::usesMigrations()) {
            yield new DoctrineMigrationsBundle();
        }
    }

    protected function configureContainer(ContainerBuilder $c, LoaderInterface $loader): void
    {
        parent::configureContainer($c, $loader);

        $c->loadFromExtension('zenstruck_foundry', [
            'persistence' => ['flush_once' => true],
            'enable_auto_refresh_with_lazy_objects' => self::usePHP84LazyObjects(),
            'global_state' => [
                GlobalStory::class,
                GlobalInvokableService::class,
            ],
            'orm' => [
                'reset' => self::usesMigrations()
                        ? [
                            'mode' => ResetDatabaseMode::MIGRATE,
                            'migrations' => [
                                'configurations' => ($configFile = self::migrationFiles()) ? $configFile : [],
                            ],
                        ]
                        : ['mode' => ResetDatabaseMode::SCHEMA],
            ],
        ]);

        if (self::usesMigrations() && self::migrationFiles() === []) {
            // if no configuration file was given in Foundry's config, let's use the main one as default.
            $c->loadFromExtension(
                'doctrine_migrations',
                include __DIR__.'/migration-configs/migration-configuration.php'
            );
        }

        $c->register(GlobalInvokableService::class);

        $c->register(DoctrineMiddleware::class)
            ->setAutowired(true)
            ->setAutoconfigured(true);

        if (self::hasORM()) {
            $c->register(OrmResetterDecorator::class)->setAutowired(true)->setAutoconfigured(true);
        }

        if (self::hasMongo()) {
            $c->register(MongoResetterDecorator::class)->setAutowired(true)->setAutoconfigured(true);
        }
    }

    public static function usesMigrations(): bool
    {
        return 'migrate' === \getenv('DATABASE_RESET_MODE');
    }

    public static function shouldGenerateMigrations(): bool
    {
        return '0' !== \getenv('DATABASE_GENERATE_MIGRATIONS');
    }

    /**
     * @return list<string>
     */
    public static function migrationFiles(): array
    {
        $files = json_decode(\getenv('MIGRATION_CONFIGURATION_FILES')?: '[]', flags: JSON_THROW_ON_ERROR);

        if (!\is_array($files)) {
            throw new \InvalidArgumentException('MIGRATION_CONFIGURATION_FILES must be a JSON array.');
        }

        foreach ($files as $file) {
            if (!\file_exists($file)) {
                throw new \InvalidArgumentException(\sprintf('Migration configuration file "%s" does not exist.', $file));
            }
        }

        return $files; // @phpstan-ignore return.type
    }
}
