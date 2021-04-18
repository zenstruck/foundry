<?php

namespace Zenstruck\Foundry\Test;

use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Console\Application;

/**
 * @internal
 */
final class ODMSchemaResetter extends AbstractSchemaResetter
{
    /** @var Application */
    private $application;
    /** @var ManagerRegistry */
    private $registry;

    public function __construct(Application $application, ManagerRegistry $registry)
    {
        $this->application = $application;
        $this->registry = $registry;
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
            } catch (\Exception $e) {
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
        return [$this->registry->getDefaultManagerName()];
    }
}
