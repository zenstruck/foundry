<?php

namespace Zenstruck\Foundry\Tests\Functional\Bundle\Extractor;

use PHPUnit\Framework\TestCase;
use Zenstruck\Foundry\Bundle\Extractor\ScalarType;

class ScalarTypeTest extends TestCase
{
    /**
     * @test
     */
    public function is_scalar_type()
    {
        $scalarType = new ScalarType();

        $this->assertTrue($scalarType::isScalarType('string'));
        $this->assertTrue($scalarType::isScalarType('integer'));
        $this->assertTrue($scalarType::isScalarType('float'));
        $this->assertTrue($scalarType::isScalarType('boolean'));
        $this->assertFalse($scalarType::isScalarType('date'));
        $this->assertFalse($scalarType::isScalarType('json'));
        $this->assertFalse($scalarType::isScalarType('array'));
        $this->assertFalse($scalarType::isScalarType('datetime'));
    }
}
