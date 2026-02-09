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
use PHPUnit\Framework\Attributes\RequiresEnvironmentVariable;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Attribute\ResetDatabase;
use Zenstruck\Foundry\Tests\Fixture\Factories\Entity\GenericEntityFactory;

/**
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 */
#[ResetDatabase]
#[RequiresEnvironmentVariable('DATABASE_URL')]
final class GenericFactoryUsingBeforeHooksTest extends KernelTestCase
{
    protected function setUp(): void
    {
        GenericEntityFactory::createOne();
    }

    #[Before(1)]
    public function beforeSetup(): void
    {
        $this->setUp();
    }

    #[Before(-1)]
    public function afterSetup(): void
    {
        $this->setUp();
    }

    #[Test]
    public function assert_objects_created(): void
    {
        GenericEntityFactory::assert()->count(3);
    }

    #[Test]
    public function assert_objects_created_2(): void
    {
        GenericEntityFactory::assert()->count(3);
    }
}
