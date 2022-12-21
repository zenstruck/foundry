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

namespace Zenstruck\Foundry\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpKernel\KernelInterface;
use Zenstruck\Assert;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Tests\Fixtures\Entity\Address;
use Zenstruck\Foundry\Tests\Fixtures\Factories\AddressFactory;
use Zenstruck\Foundry\Tests\Fixtures\Kernel;

final class WithDoctrineDisabledKernelTest extends KernelTestCase
{
    use Factories;

    public static function setUpBeforeClass(): void
    {
        if (\getenv('USE_DAMA_DOCTRINE_TEST_BUNDLE')) {
            self::markTestSkipped('dama/doctrine-test-bundle should not be enabled.');
        }
    }

    /**
     * @test
     */
    public function create_object(): void
    {
        $address = AddressFactory::new()->withoutPersisting()->create(['value' => 'test'])->object();
        Assert::that($address)->isInstanceOf(Address::class);
        Assert::that($address->getValue())->is('test');

        $address = AddressFactory::createOne(['value' => 'test'])->object();
        Assert::that($address)->isInstanceOf(Address::class);
        Assert::that($address->getValue())->is('test');
    }

    protected static function createKernel(array $options = []): KernelInterface
    {
        return Kernel::create(false);
    }
}
