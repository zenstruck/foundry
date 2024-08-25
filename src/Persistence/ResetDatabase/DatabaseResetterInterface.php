<?php

declare(strict_types=1);

namespace Zenstruck\Foundry\Persistence\ResetDatabase;

use Symfony\Component\HttpKernel\KernelInterface;

interface DatabaseResetterInterface
{
    public function resetDatabase(KernelInterface $kernel): void;
}
