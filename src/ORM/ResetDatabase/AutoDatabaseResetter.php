<?php

namespace Zenstruck\Foundry\ORM\ResetDatabase;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Migrations\DependencyFactory;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 * @internal
 */
final class AutoDatabaseResetter implements OrmResetter
{
    private OrmResetter $inner;

    /**
     * @param list<string> $configurations
     */
    public function __construct(
        private readonly array $configurations,
        private Registry $registry,
        private array $managers,
        private array $connections,
        private ?DependencyFactory $dependencyFactory,
    ) {
    }

    public function resetBeforeFirstTest(KernelInterface $kernel): void
    {
        $this->inner()->resetBeforeFirstTest($kernel);
    }

    public function resetBeforeEachTest(KernelInterface $kernel): void
    {
        $this->inner()->resetBeforeEachTest($kernel);
    }

    private function inner(): OrmResetter
    {
        if (isset($this->inner)) {
            return $this->inner;
        }

        if (!$this->hasMigrations()) {
            return $this->inner = new SchemaDatabaseResetter(
                $this->registry,
                $this->managers,
                $this->connections,
            );
        }

        return $this->inner = new MigrateDatabaseResetter(
            $this->configurations,
            $this->registry,
            $this->managers,
            $this->connections,
        );
    }

    private function hasMigrations(): bool
    {
        if (!$this->dependencyFactory) {
            return false;
        }

        return 0 !== $this->dependencyFactory->getMigrationPlanCalculator()->getMigrations()->count();
    }
}
