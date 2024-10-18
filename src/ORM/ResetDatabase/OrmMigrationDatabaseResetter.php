<?php

declare(strict_types=1);

namespace Zenstruck\Foundry\ORM\ResetDatabase;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * @internal
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 */
final class OrmMigrationDatabaseResetter extends AbstractOrmResetter implements OrmDatabaseResetterInterface
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

    final public function resetSchema(KernelInterface $kernel): void
    {
        $this->resetWithMigration($kernel);
    }

    public function resetDatabase(KernelInterface $kernel): void
    {
        $this->resetWithMigration($kernel);
    }

    private function resetWithMigration(KernelInterface $kernel): void
    {
        $application = self::application($kernel);

        $this->dropAndResetDatabase($application);

        if (!$this->configurations) {
            self::runCommand($application, 'doctrine:migrations:migrate');

            return;
        }

        foreach ($this->configurations as $configuration) {
            self::runCommand($application, 'doctrine:migrations:migrate', ['--configuration' => $configuration]);
        }
    }
}
