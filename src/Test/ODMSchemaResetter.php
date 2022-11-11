<?php

namespace Zenstruck\Foundry\Test;

use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Console\Application;

/**
 * @internal
 */
final class ODMSchemaResetter extends AbstractSchemaResetter
{
    /** @var list<string> */
    private array $objectManagersToReset = [];

    /**
     * @param string[] $objectManagersToReset
     */
    public function __construct(private Application $application, private ManagerRegistry $registry, array $objectManagersToReset)
    {
        self::validateObjectsToReset('object manager', \array_keys($registry->getManagerNames()), $objectManagersToReset);
        $this->objectManagersToReset = $objectManagersToReset;
    }

    public function resetSchema(): void
    {
        foreach ($this->objectManagersToReset() as $manager) {
            try {
                $this->runCommand(
                    $this->application,
                    'doctrine:mongodb:schema:drop',
                    [
                        '--dm' => $manager,
                    ]
                );
            } catch (\Exception) {
            }

            $this->runCommand(
                $this->application,
                'doctrine:mongodb:schema:create',
                [
                    '--dm' => $manager,
                ]
            );
        }
    }

    /** @return list<string> */
    private function objectManagersToReset(): array
    {
        return $this->objectManagersToReset ?: [$this->registry->getDefaultManagerName()];
    }
}
