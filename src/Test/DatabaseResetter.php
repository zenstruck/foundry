<?php

namespace Zenstruck\Foundry\Test;

use DAMA\DoctrineTestBundle\Doctrine\DBAL\StaticDriver;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\HttpKernel\KernelInterface;
use Zenstruck\Foundry\Factory;

/**
 * @internal
 *
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class DatabaseResetter
{
    /** @var bool */
    private static $hasBeenReset = false;

    public static function hasBeenReset(): bool
    {
        if (isset($_SERVER['FOUNDRY_DISABLE_DATABASE_RESET'])) {
            return true;
        }

        return self::$hasBeenReset;
    }

    public static function isDAMADoctrineTestBundleEnabled(): bool
    {
        return \class_exists(StaticDriver::class) && StaticDriver::isKeepStaticConnections();
    }

    public static function resetDatabase(KernelInterface $kernel): void
    {
        $application = self::createApplication($kernel);
        $registry = $kernel->getContainer()->get('doctrine');

        foreach (self::connectionsToReset($registry) as $connection) {
            $dropParams = ['--connection' => $connection, '--force' => true];

            if ('sqlite' !== $registry->getConnection($connection)->getDatabasePlatform()->getName()) {
                // sqlite does not support "--if-exists" (ref: https://github.com/doctrine/dbal/pull/2402)
                $dropParams['--if-exists'] = true;
            }

            self::runCommand($application, 'doctrine:database:drop', $dropParams);

            self::runCommand($application, 'doctrine:database:create', [
                '--connection' => $connection,
            ]);
        }

        self::createSchema($application, $registry);

        self::$hasBeenReset = true;
    }

    public static function resetSchema(KernelInterface $kernel): void
    {
        $application = self::createApplication($kernel);
        $registry = $kernel->getContainer()->get('doctrine');

        self::dropSchema($application, $registry);
        self::createSchema($application, $registry);
    }

    private static function createSchema(Application $application, ManagerRegistry $registry): void
    {
        foreach (self::objectManagersToReset($registry) as $manager) {
            self::runCommand($application, 'doctrine:schema:create', [
                '--em' => $manager,
            ]);
        }

        if (!Factory::isBooted()) {
            TestState::bootFromContainer($application->getKernel()->getContainer());
        }

        TestState::flushGlobalState();
    }

    private static function dropSchema(Application $application, ManagerRegistry $registry): void
    {
        foreach (self::objectManagersToReset($registry) as $manager) {
            self::runCommand($application, 'doctrine:schema:drop', [
                '--em' => $manager,
                '--force' => true,
            ]);
        }
    }

    private static function runCommand(Application $application, string $command, array $parameters = []): void
    {
        $exit = $application->run(
            new ArrayInput(\array_merge(['command' => $command], $parameters)),
            $output = new BufferedOutput()
        );

        if (0 !== $exit) {
            throw new \RuntimeException(\sprintf('Error running "%s": %s', $command, $output->fetch()));
        }
    }

    private static function createApplication(KernelInterface $kernel): Application
    {
        $application = new Application($kernel);
        $application->setAutoExit(false);

        return $application;
    }

    private static function connectionsToReset(ManagerRegistry $registry): array
    {
        if (isset($_SERVER['FOUNDRY_RESET_CONNECTIONS'])) {
            return \explode(',', $_SERVER['FOUNDRY_RESET_CONNECTIONS']);
        }

        return [$registry->getDefaultConnectionName()];
    }

    private static function objectManagersToReset(ManagerRegistry $registry): array
    {
        if (isset($_SERVER['FOUNDRY_RESET_OBJECT_MANAGERS'])) {
            return \explode(',', $_SERVER['FOUNDRY_RESET_OBJECT_MANAGERS']);
        }

        return [$registry->getDefaultManagerName()];
    }
}
