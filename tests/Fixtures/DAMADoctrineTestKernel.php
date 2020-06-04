<?php

namespace Zenstruck\Foundry\Tests\Fixtures;

use DAMA\DoctrineTestBundle\DAMADoctrineTestBundle;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class DAMADoctrineTestKernel extends Kernel
{
    public function registerBundles(): iterable
    {
        yield from parent::registerBundles();

        yield new DAMADoctrineTestBundle();
    }
}
