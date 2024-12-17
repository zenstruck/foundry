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

use function Zenstruck\Foundry\application;
use function Zenstruck\Foundry\runCommand;

/**
 * @internal
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 */
final class SchemaDatabaseResetter extends BaseOrmResetter
{
    public function resetBeforeFirstTest(KernelInterface $kernel): void
    {
        $application = application($kernel);

        $this->dropAndResetDatabase($application);
        $this->createSchema($application);
    }

    protected function doResetBeforeEachTest(KernelInterface $kernel): void
    {
        $application = application($kernel);

        $this->dropSchema($application);
        $this->createSchema($application);
    }

    private function createSchema(Application $application): void
    {
        foreach ($this->managers as $manager) {
            runCommand($application, "doctrine:schema:update --em={$manager} --force -v");
        }
    }

    private function dropSchema(Application $application): void
    {
        foreach ($this->managers as $manager) {
            runCommand($application, "doctrine:schema:drop --em={$manager} --force --full-database");
        }
    }
}
