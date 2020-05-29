<?php

namespace Zenstruck\Foundry\Tests;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
abstract class FunctionalTestCase extends KernelTestCase
{
    use ResetDatabase, Factories;
}
