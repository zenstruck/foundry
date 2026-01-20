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

namespace Zenstruck\Foundry\Tests\Integration\Faker;

use PHPUnit\Framework\Attributes\RequiresPhpunit;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpKernel\KernelInterface;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;
use Zenstruck\Foundry\Tests\Fixture\ExtendedGenerator;
use function Zenstruck\Foundry\faker;

/**
 * @author Silas Joisten <silasjoisten@proton.me>
 * @requires PHPUnit >=11.0
 */
#[RequiresPhpunit('>=11.0')]
final class FakerCustomServiceKernelTest extends KernelTestCase
{
    use Factories, ResetDatabase;

    #[Test]
    public function faker_service_can_be_set(): void
    {
        self::assertInstanceOf(ExtendedGenerator::class, faker());
        self::assertSame('custom', faker()->customMethod());
    }

    protected static function bootKernel(array $options = []): KernelInterface
    {
        return parent::bootKernel(['environment' => 'faker_custom_service']);
    }
}
