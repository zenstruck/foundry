<?php

namespace Zenstruck\Foundry\Tests\Functional\Bundle\Extractor;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Bundle\Extractor\DoctrineTypes;
use Zenstruck\Foundry\Bundle\Extractor\Property;

class PropertyTest extends KernelTestCase
{
    /**
     * @var Property|object|null
     */
    private $property;

    protected function setUp(): void
    {
        self::bootKernel();
        $em = static::$container->get('doctrine.orm.default_entity_manager');
        $this->property = new Property($em);
    }

    /**
     * @test
     * @dataProvider doctrineTypes
     */
    public function generated_faker_method(string $doctrineType)
    {
        $createdFakerMethodAsString = $this->property->createFakerMethodFromDoctrineType($doctrineType);

        $this->assertEquals(DoctrineTypes::DOCTRINE_TYPES[$doctrineType], $createdFakerMethodAsString);
    }

    public function doctrineTypes()
    {
        return [
            ['ARRAY'],
            ['ASCII_STRING'],
            ['BIGINT'],
            ['BLOB'],
            ['BOOLEAN'],
            ['DATE_MUTABLE'],
            ['DATE_IMMUTABLE'],
            ['DATETIME_MUTABLE'],
            ['DATETIME_IMMUTABLE'],
            ['DATETIMETZ_MUTABLE'],
            ['DATETIMETZ_IMMUTABLE'],
            ['DECIMAL'],
            ['FLOAT'],
            ['INTEGER'],
            ['JSON'],
            ['JSON_ARRAY'],
            ['SIMPLE_ARRAY'],
            ['SMALLINT'],
            ['STRING'],
            ['TEXT'],
            ['TIME_MUTABLE'],
            ['TIME_IMMUTABLE'],
        ];
    }
}
