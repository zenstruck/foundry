<?php

namespace Zenstruck\Foundry\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Zenstruck\Foundry\Proxy;
use Zenstruck\Foundry\Tests\Fixtures\Entity\Category;
use Zenstruck\Foundry\Tests\ResetGlobals;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ProxyTest extends TestCase
{
    use ResetGlobals;

    /**
     * @test
     */
    public function can_force_get_and_set_non_public_properties(): void
    {
        $proxy = (new Proxy(new Category()))->withoutAutoRefresh();

        $this->assertNull($proxy->forceGet('name'));

        $proxy->forceSet('name', 'foo');

        $this->assertSame('foo', $proxy->forceGet('name'));
    }

    /**
     * @test
     */
    public function can_access_wrapped_objects_properties(): void
    {
        $proxy = (new Proxy(new class() {
            public $property;
        }))->withoutAutoRefresh();

        $this->assertFalse(isset($proxy->property));

        $proxy->property = 'foo';

        $this->assertSame('foo', $proxy->property);

        $this->assertTrue(isset($proxy->property));

        unset($proxy->property);

        $this->assertFalse(isset($proxy->property));
    }
}
