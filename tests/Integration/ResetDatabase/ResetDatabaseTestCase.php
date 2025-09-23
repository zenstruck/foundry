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

namespace Zenstruck\Foundry\Tests\Integration\ResetDatabase;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Attribute\ResetDatabase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase as ResetDatabaseTrait;
use Zenstruck\Foundry\Tests\Fixture\ResetDatabase\ResetDatabaseTestKernel;

#[ResetDatabase]
abstract class ResetDatabaseTestCase extends KernelTestCase
{
    use Factories, ResetDatabaseTrait;

    protected static function getKernelClass(): string
    {
        return ResetDatabaseTestKernel::class;
    }
}
