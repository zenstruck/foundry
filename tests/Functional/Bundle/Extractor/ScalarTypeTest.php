<?php

namespace Zenstruck\Foundry\Tests\Functional\Bundle\Extractor;

use Zenstruck\Foundry\Bundle\Extractor\ScalarType;
use PHPUnit\Framework\TestCase;

class ScalarTypeTest extends TestCase
{
    public function testIsScalarType()
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
