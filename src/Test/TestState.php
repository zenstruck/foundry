<?php

namespace Zenstruck\Foundry\Test;

use Faker;
use Psr\Container\ContainerInterface;
use Zenstruck\Foundry\Factory;
use Zenstruck\Foundry\Manager;
use Zenstruck\Foundry\StoryManager;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class TestState
{
    /** @var callable|null */
    private static $instantiator;
    private static ?Faker\Generator $faker = null;
    private static bool $useBundle = true;

    public static function setInstantiator(callable $instantiator): void
    {
        self::$instantiator = $instantiator;
    }

    public static function setFaker(Faker\Generator $faker): void
    {
        self::$faker = $faker;
    }

    public static function withoutBundle(): void
    {
        self::$useBundle = false;
    }

    public static function bootFactory(ContainerInterface $container): Manager
    {
        $manager = self::$useBundle ? $container->get(Manager::class) : new Manager($container->get('doctrine'), new StoryManager([]));

        if (self::$instantiator) {
            $manager->setInstantiator(self::$instantiator);
        }

        if (self::$faker) {
            $manager->setFaker(self::$faker);
        }

        Factory::boot($manager);

        return $manager;
    }
}
