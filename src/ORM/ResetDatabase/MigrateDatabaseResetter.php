<?php

declare(strict_types=1);

namespace Zenstruck\Foundry\ORM\ResetDatabase;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\HttpKernel\KernelInterface;

use function Zenstruck\Foundry\application;
use function Zenstruck\Foundry\runCommand;

/**
 * @internal
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 */
final class MigrateDatabaseResetter extends BaseOrmResetter
{
    /**
     * @param list<string> $configurations
     */
    public function __construct(
        private readonly array $configurations,
        Registry $registry,
        array $managers,
        array $connections,
    )
    {
        parent::__construct($registry, $managers, $connections);
    }

    final public function resetBeforeFirstTest(KernelInterface $kernel): void
    {
        $this->resetWithMigration($kernel);
    }

    public function doResetBeforeEachTest(KernelInterface $kernel): void
    {
        $this->resetWithMigration($kernel);
    }

    private function resetWithMigration(KernelInterface $kernel): void
    {
        $application = application($kernel);

        $this->dropAndResetDatabase($application);

        if (!$this->configurations) {
            runCommand($application, 'doctrine:migrations:migrate');

            return;
        }

        foreach ($this->configurations as $configuration) {
            runCommand($application, "doctrine:migrations:migrate --configuration={$configuration}");
        }
    }
}
