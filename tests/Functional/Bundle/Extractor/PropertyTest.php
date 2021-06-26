<?php

namespace Zenstruck\Foundry\Tests\Functional\Bundle\Extractor;

use DateTime;
use ReflectionClass;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Url;
use Zenstruck\Foundry\Bundle\Extractor\Property;
use Zenstruck\Foundry\Test\Factories;

class PropertyTest extends KernelTestCase
{
    use Factories;

    /**
     * @var Property|object|null
     */
    private $property;

    protected function setUp(): void
    {
        self::bootKernel();
        $em = static::getContainer()->get('doctrine.orm.default_entity_manager');
        $this->property = new Property($em);
        //$this->property = static::getContainer()->get('Zenstruck\Foundry\Bundle\Extractor\Property');
    }

    /**
     * @TODO add an TestEntity in this bundle to decouple from Project and possibility to test all Cases
     *
     * @test
     */
    public function get_probs_from_entity()
    {
        // @TODO replace User Entity with Fixtures
        $this->markTestSkipped('replace User Entity with Fixtures');

        $ref = new ReflectionClass('App\Entity\User');
        $probs = $this->property->getScalarPropertiesFromDoctrineFieldMappings($ref);

        $this->assertCount(6, $probs);
        $this->assertIsArray($probs['roles']);
        $this->assertEquals('ROLE_USER', $probs['roles'][0]);
        $this->assertTrue($probs['isVerifiedAccount']);
        $this->assertEquals(10, \mb_strlen($probs['lastLogin']));
    }

    /**
     * @test
     */
    public function generate_email()
    {
        $email = $this->property->createScalarProperties('string', [], 'email');

        $this->assertStringContainsString('@', $email);
    }

    /**
     * @test
     */
    public function generate_firstname()
    {
        $firstName = $this->property->createScalarProperties('string', [], 'firstName');

        $this->assertIsString($firstName);
    }

    /**
     * @test
     */
    public function generate_integer()
    {
        $int = $this->property->createScalarProperties('integer', [], 'postalCode');

        $this->assertIsInt($int);
    }

    /**
     * @test
     */
    public function generate_bool()
    {
        $bool = $this->property->createScalarProperties('boolean', [], 'isActive');

        $this->assertTrue($bool);
        $this->assertIsBool($bool);
    }

    /**
     * @test
     */
    public function generate_float()
    {
        $float = $this->property->createScalarProperties('float', [], 'price');

        $this->assertIsFloat($float);
    }

    /**
     * @test
     */
    public function create_json_property_with_field_name_roles()
    {
        $roleUser = $this->property->createJsonPropertyFromAnnotationOrFieldName([], 'roles');

        $this->assertIsArray($roleUser);
        $this->assertEquals('ROLE_USER', $roleUser[0]);
    }

    /**
     * @test
     */
    public function create_json_property_with_default()
    {
        $roleUser = $this->property->createJsonPropertyFromAnnotationOrFieldName([], 'foo');

        $this->assertIsArray($roleUser);
        $this->assertEquals('DEFAULT', $roleUser[0]);
    }

    /**
     * @test
     */
    public function create_string_property_from_annotation_or_field_name_with_field_name()
    {
        $displayName = $this->property->createStringPropertyFromAnnotationOrFieldName([], 'displayName');

        $this->assertIsString($displayName);
    }

    /**
     * @test
     */
    public function create_string_property_from_annotation_or_field_name_with_annotation_email()
    {
        // @TODO investigate here
        $this->markTestSkipped('Symfony Constraints missing');

        $emailConstraint = new Email();
        $email = $this->property->createStringPropertyFromAnnotationOrFieldName([$emailConstraint], 'displayName');

        $this->assertStringContainsString('@', $email);
    }

    /**
     * @test
     */
    public function create_string_property_from_annotation_or_field_name_with_annotation_url()
    {
        // @TODO investigate here
        $this->markTestSkipped('Symfony Constraints missing');

        $urlConstraint = new Url();
        $url = $this->property->createStringPropertyFromAnnotationOrFieldName([$urlConstraint], 'displayName');

        $this->assertStringContainsString('http', $url);
    }

    /**
     * @test
     */
    public function create_date()
    {
        // reset our properties
        $this->property->setProperties([]);

        $this->property->createDateTimeProperty('someNiceDateField');

        // assert
        $props = $this->property->getProperties();
        $date = new DateTime($props['someNiceDateField']);
        $this->assertCount(1, $props);
        $this->assertEquals(10, \mb_strlen($props['someNiceDateField']));
        $this->assertInstanceOf(DateTime::class, $date);
    }
}
