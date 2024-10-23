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

namespace Zenstruck\Foundry\Mongo;

use Symfony\Component\HttpKernel\KernelInterface;

use function Zenstruck\Foundry\application;
use function Zenstruck\Foundry\runCommand;

/**
 * @internal
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 */
final class MongoSchemaResetter implements MongoResetter
{
    /**
     * @param list<string> $managers
     */
    public function __construct(private array $managers)
    {
    }

    public function resetBeforeEachTest(KernelInterface $kernel): void
    {
        $application = application($kernel);

        foreach ($this->managers as $manager) {
            try {
                runCommand($application, "doctrine:mongodb:schema:drop --dm={$manager}");
            } catch (\Exception) {
            }

            runCommand($application, "doctrine:mongodb:schema:create --dm={$manager}");
        }
    }
}
