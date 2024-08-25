<?php

declare(strict_types=1);

namespace Zenstruck\Foundry\Mongo;

use Symfony\Component\HttpKernel\KernelInterface;
use Zenstruck\Foundry\Persistence\SymfonyCommandRunner;

/**
 * @internal
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 */
final class MongoSchemaResetter implements MongoResetter
{
    use SymfonyCommandRunner;

    /**
     * @param list<string> $managers
     */
    public function __construct(private array $managers)
    {
    }

    public function resetBeforeEachTest(KernelInterface $kernel): void
    {
        $application = self::application($kernel);

        foreach ($this->managers as $manager) {
            try {
                self::runCommand($application, 'doctrine:mongodb:schema:drop', ['--dm' => $manager]);
            } catch (\Exception) {
            }

            self::runCommand($application, 'doctrine:mongodb:schema:create', ['--dm' => $manager]);
        }
    }
}
