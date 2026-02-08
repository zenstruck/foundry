<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Tests\Fixture\ResetDatabase;

use Symfony\Component\DependencyInjection\Attribute\AsDecorator;
use Symfony\Component\HttpKernel\KernelInterface;
use Zenstruck\Foundry\Mongo\MongoResetter;

#[AsDecorator(MongoResetter::class)]
final class MongoResetterDecorator implements MongoResetter
{
    public static bool $calledBeforeEachTest = false;

    public function __construct(
        private MongoResetter $decorated,
    ) {
    }

    public function resetBeforeEachTest(KernelInterface $kernel): void
    {
        self::$calledBeforeEachTest = true;

        $this->decorated->resetBeforeEachTest($kernel);
    }
}
