<?php

declare(strict_types=1);

namespace Zenstruck\Foundry\Tests\Fixture\ResetDatabase;

use Symfony\Component\DependencyInjection\Attribute\AsDecorator;
use Symfony\Component\HttpKernel\KernelInterface;
use Zenstruck\Foundry\ORM\ResetDatabase\OrmResetter;

#[AsDecorator(OrmResetter::class)]
final class OrmResetterDecorator implements OrmResetter
{
    public static bool $calledBeforeFirstTest = false;
    public static bool $calledBeforeEachTest = false;

    public function __construct(
        private OrmResetter $decorated
    ) {}

    public function resetBeforeFirstTest(KernelInterface $kernel): void
    {
        self::$calledBeforeFirstTest = true;

        $this->decorated->resetBeforeFirstTest($kernel);
    }

    public function resetBeforeEachTest(KernelInterface $kernel): void
    {
        self::$calledBeforeEachTest = true;

        $this->decorated->resetBeforeEachTest($kernel);
    }

    public static function reset(): void
    {
        self::$calledBeforeFirstTest = false;
        self::$calledBeforeEachTest = false;
    }
}
