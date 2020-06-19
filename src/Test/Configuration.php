<?php

namespace Zenstruck\Foundry\Test;

use Faker;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Zenstruck\Foundry\Factory;
use Zenstruck\Foundry\Manager;
use Zenstruck\Foundry\StoryManager;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class Configuration
{
    /** @var callable|null */
    private static $instantiator;
    private static ?Faker\Generator $faker = null;

    public static function setInstantiator(callable $instantiator): void
    {
        self::$instantiator = $instantiator;
    }

    public static function setFaker(Faker\Generator $faker): void
    {
        self::$faker = $faker;
    }

    public static function bootFactory(ContainerInterface $container): Manager
    {
        try {
            $manager = $container->get(Manager::class);
        } catch (NotFoundExceptionInterface $e) {
            // bundle not enabled
            Factory::boot($manager = new Manager($container->get('doctrine'), new StoryManager([])));
        }

        if (self::$instantiator) {
            $manager->setInstantiator(self::$instantiator);
        }

        if (self::$faker) {
            $manager->setFaker(self::$faker);
        }

        return $manager;
    }
}
