<?php

declare(strict_types=1);

namespace Zenstruck\Foundry\Tests\Fixture\ResetDatabase;

use Symfony\Component\DependencyInjection\Attribute\AsDecorator;
use Symfony\Component\HttpKernel\KernelInterface;
use Zenstruck\Foundry\Mongo\MongoResetter;

#[AsDecorator(MongoResetter::class)]
final class MongoResetterDecorator implements MongoResetter
{
    public static bool $calledBeforeEachTest = false;

    public function __construct(
        private MongoResetter $decorated
    ) {
    }

    public function resetBeforeEachTest(KernelInterface $kernel): void
    {
        self::$calledBeforeEachTest = true;

        $this->decorated->resetBeforeEachTest($kernel);
    }
}
