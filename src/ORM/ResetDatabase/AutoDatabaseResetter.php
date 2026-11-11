<?php

declare(strict_types=1);

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\ORM\ResetDatabase;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Migrations\DependencyFactory;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Resets with migrations if the app has any, otherwise with the schema.
 *
 * @author Kevin Bond <kevinbond@gmail.com>
 * @internal
 */
final class AutoDatabaseResetter implements OrmResetter
{
    private ?OrmResetter $inner = null;

    /**
     * @param list<string> $managers
     * @param list<string> $connections
     */
    public function __construct(
        private readonly Registry $registry,
        private readonly array $managers,
        private readonly array $connections,
        private readonly ?DependencyFactory $dependencyFactory = null,
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
        return $this->inner ??= $this->hasMigrations()
            ? new MigrateDatabaseResetter([], $this->registry, $this->managers, $this->connections)
            : new SchemaDatabaseResetter($this->registry, $this->managers, $this->connections);
    }

    private function hasMigrations(): bool
    {
        if (!$this->dependencyFactory) {
            return false;
        }

        return 0 !== $this->dependencyFactory->getMigrationRepository()->getMigrations()->count();
    }
}
