<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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
