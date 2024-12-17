<?php

declare(strict_types=1);

namespace Zenstruck\Foundry\Tests\Integration\ResetDatabase;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;
use Zenstruck\Foundry\Tests\Fixture\ResetDatabase\ResetDatabaseTestKernel;

abstract class ResetDatabaseTestCase extends KernelTestCase
{
    use Factories, ResetDatabase;

    protected static function getKernelClass(): string
    {
        return ResetDatabaseTestKernel::class;
    }
}
