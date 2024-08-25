<?php

declare(strict_types=1);

namespace Zenstruck\Foundry\ORM;

use Symfony\Component\HttpKernel\KernelInterface;
use Zenstruck\Foundry\Persistence\ResetDatabase\DatabaseResetterInterface;
use Zenstruck\Foundry\Persistence\SymfonyCommandRunner;

/**
 * @internal
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 */
final class OrmDatabaseResetter implements DatabaseResetterInterface
{
    use OrmDatabaseResetterTrait;
    use SymfonyCommandRunner;

    /**
     * @param list<string> $managers
     * @param list<string> $connections
     */
    public function __construct(
        private array $managers,
        private array $connections,
    ) {
    }

    final public function resetDatabase(KernelInterface $kernel): void
    {
        $application = self::application($kernel);

        $this->dropAndResetDatabase($application);
        $this->createSchema($application);
    }

    private function managers(): array
    {
        return $this->managers;
    }

    private function connections(): array
    {
        return $this->connections;
    }
}
