<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Tests\Integration\Persistence;

use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;
use Zenstruck\Foundry\Tests\Fixture\Factories\Entity\GenericEntityFactory;
use Zenstruck\Foundry\Tests\Integration\RequiresORM;

/**
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 */
abstract class GenericFactoryUsingBeforeHooksTestCase extends KernelTestCase
{
    use Factories, RequiresORM, ResetDatabase;

    protected function setUp(): void
    {
        GenericEntityFactory::createOne();
    }

    /** @before */
    #[Before(1)]
    public function beforeSetup(): void
    {
        $this->setUp();
    }

    /** @before */
    #[Before(-1)]
    public function afterSetup(): void
    {
        $this->setUp();
    }

    /**
     * @test
     */
    #[Test]
    public function assert_objects_created(): void
    {
        GenericEntityFactory::assert()->count(3);
    }

    /**
     * @test
     */
    #[Test]
    public function assert_objects_created_2(): void
    {
        GenericEntityFactory::assert()->count(3);
    }
}
