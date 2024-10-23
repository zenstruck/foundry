<?php

declare(strict_types=1);

namespace Zenstruck\Foundry\Persistence\ResetDatabase;

use Symfony\Component\HttpKernel\KernelInterface;

/**
 * @internal
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 */
interface BeforeFirstTestResetter
{
    public function resetBeforeFirstTest(KernelInterface $kernel): void;
}
