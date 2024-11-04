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

namespace Zenstruck\Foundry\Persistence\ResetDatabase;

use Symfony\Component\HttpKernel\KernelInterface;

/**
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 */
interface BeforeEachTestResetter
{
    public function resetBeforeEachTest(KernelInterface $kernel): void;
}
