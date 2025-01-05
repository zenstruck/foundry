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

        if (FoundryTestKernel::usesMigrations()) {
            yield new DoctrineMigrationsBundle();
        }
    }

    protected function configureContainer(ContainerBuilder $c, LoaderInterface $loader): void
    {
        parent::configureContainer($c, $loader);

        $c->loadFromExtension('zenstruck_foundry', [
            'global_state' => [
                GlobalStory::class,
                GlobalInvokableService::class,
            ],
            'orm' => [
                'reset' => FoundryTestKernel::usesMigrations()
                        ? [
                            'mode' => ResetDatabaseMode::MIGRATE,
                            'migrations' => [
                                'configurations' => ($configFile = \getenv('MIGRATION_CONFIGURATION_FILE')) ? [$configFile] : [],
                            ],
                        ]
                        : ['mode' => ResetDatabaseMode::SCHEMA],
            ],
        ]);

        if (FoundryTestKernel::usesMigrations() && !\getenv('MIGRATION_CONFIGURATION_FILE')) {
            // if no configuration file was given in Foundry's config, let's use the main one as default.
            $c->loadFromExtension(
                'doctrine_migrations',
                include __DIR__.'/migration-configs/migration-configuration.php'
            );
        }

        $c->register(GlobalInvokableService::class);

        if (self::hasORM()) {
            $c->register(OrmResetterDecorator::class)->setAutowired(true)->setAutoconfigured(true);
        }

        if (self::hasMongo()) {
            $c->register(MongoResetterDecorator::class)->setAutowired(true)->setAutoconfigured(true);
        }
    }
}
