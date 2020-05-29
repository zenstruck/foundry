<?php

namespace Zenstruck\Foundry\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Zenstruck\Foundry\Proxy;
use Zenstruck\Foundry\Tests\Fixtures\Entity\Category;
use Zenstruck\Foundry\Tests\ResetGlobalState;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ProxyTest extends TestCase
{
    use ResetGlobalState;

    protected function setUp(): void
    {
        parent::setUp();

        Proxy::autoRefreshByDefault(false);
    }

    /**
     * @test
     */
    public function can_force_get_and_set_non_public_properties(): void
    {
        $proxy = new Proxy(new Category());

        $this->assertNull($proxy->forceGet('name'));

        $proxy->forceSet('name', 'foo');

        $this->assertSame('foo', $proxy->forceGet('name'));
    }

    /**
     * @test
     */
    public function can_access_wrapped_objects_properties(): void
    {
        $proxy = new Proxy(new class() {
            public $property;
        });

        $this->assertFalse(isset($proxy->property));

        $proxy->property = 'foo';

        $this->assertSame('foo', $proxy->property);

        $this->assertTrue(isset($proxy->property));

        unset($proxy->property);

        $this->assertFalse(isset($proxy->property));
    }
}
