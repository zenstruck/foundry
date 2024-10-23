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

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * @internal
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 */
final class SchemaDatabaseResetter extends BaseOrmResetter implements OrmResetter
{
    public function resetBeforeFirstTest(KernelInterface $kernel): void
    {
        $application = self::application($kernel);

        $this->dropAndResetDatabase($application);
        $this->createSchema($application);
    }

    public function resetBeforeEachTest(KernelInterface $kernel): void
    {
        $application = self::application($kernel);

        $this->dropSchema($application);
        $this->createSchema($application);
    }

    private function createSchema(Application $application): void
    {
        foreach ($this->managers as $manager) {
            self::runCommand(
                $application,
                'doctrine:schema:update',
                ['--em' => $manager, '--force' => true]
            );
        }
    }

    private function dropSchema(Application $application): void
    {
        foreach ($this->managers as $manager) {
            self::runCommand(
                $application,
                'doctrine:schema:drop',
                ['--em' => $manager, '--force' => true, '--full-database' => true]
            );
        }
    }
}
