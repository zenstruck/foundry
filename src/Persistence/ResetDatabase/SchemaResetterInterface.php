<?php

declare(strict_types=1);

namespace Zenstruck\Foundry\Persistence\ResetDatabase;

use Symfony\Component\HttpKernel\KernelInterface;

interface SchemaResetterInterface
{
    public function resetSchema(KernelInterface $kernel): void;
}
