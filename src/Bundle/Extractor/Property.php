<?php

namespace Zenstruck\Foundry\Bundle\Extractor;

use Zenstruck\Foundry\Bundle\Extractor\Type\JsonDetectionList;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use ReflectionClass;
use ReflectionProperty;
use function Zenstruck\Foundry\faker;

class Property
{
    private array $properties = [];
    private array $defaultProperties = [];
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * @TODO Support for defaults is missing
     * @TODO Support for relations is missing
     *
     * We only want create Defaults for non NULL probs
     * Dont create Default for field id
     * Based on fieldNames or Constraints or Types
     */
    public function getScalarPropertiesFromDoctrineFieldMappings(ReflectionClass $entity): array
    {
        $reader = new \Doctrine\Common\Annotations\AnnotationReader();

        $classMetaData = $this->em->getClassMetadata($entity->getName());
        $this->getDefaultFromProperty($entity);
        $identifierFieldNames = $classMetaData->getIdentifierFieldNames();


        foreach ($classMetaData->fieldMappings as $property) {

            // identifiers are normally autogenerated values. (id)
            if (in_array($property['fieldName'], $identifierFieldNames)) {
                continue;
            }

            // CREATE SCARLAR
            if (!$property['nullable']
                && true === ScalarType::isScalarType($property['type'])) {

                // Also we should check property name and as examble for things like company create faker()->company()
                // if there is an default property which is not null|''|false we can use it? $this->>defaultProperties
                $reflectionProperty = new ReflectionProperty($entity->getName(), $property['fieldName']);
                $propertyAnnotation = $reader->getPropertyAnnotations($reflectionProperty);
                $this->properties[$property['fieldName']] = $this->createScalarProperties($property['type'], $propertyAnnotation, $property['fieldName']);
            }

            // CREATE JSON
            if (!$property['nullable']
                && 'json' === $property['type']) {

                $this->createJsonProperty($propertyAnnotation, $property['fieldName']);
            }

            // CREATE DATETIME
            if (!$property['nullable']
                && 'datetime' === $property['type'] ) {

                $this->createDateTimeProperty($property['fieldName']);
            }
        }

        return $this->properties;
    }

    /**
     * creates with faker nice default values for Scalar Types
     *
     * @return mixed <int|string|float|bool>
     * @throws Exception
     */
    public function createScalarProperties(string $type, $propertyAnnotation, string $fieldName)
    {
        switch ($type) {
            case 'string';
                // check propertyAnnotation
                return $this->createStringPropertyFromAnnotationOrFieldName($propertyAnnotation, $fieldName);
            case 'integer':
                return faker()->randomNumber();
            case 'boolean':
                return true; // is a bool always true?
            case 'float':
                return faker()->randomFloat();

            default:
                throw new Exception('type not found: ' . $type);
        }
    }

    public function createJsonProperty($propertyAnnotation, string $fieldName)
    {
        $this->properties[$fieldName] = $this->createJsonPropertyFromAnnotationOrFieldName($propertyAnnotation, $fieldName);
    }

    public function createDateTimeProperty(string $fieldName)
    {
        $this->properties[$fieldName] =  faker()->dateTime()->format('Y-m-d');
    }

    public function createDateProperty(string $fieldName)
    {
        $this->properties[$fieldName] =  faker()->date();
    }

    public function createStringPropertyFromAnnotationOrFieldName(array $propertyAnnotations, $fieldName): string
    {
        foreach ($propertyAnnotations as $key => $constraint) {
            $classname = get_class($constraint);
            // Now we can look for keys contains to generate different string with faker..
            // Email, Url, Hostname, Uuid,
                switch ($classname) {
                    case 'Symfony\Component\Validator\Constraints\Email';
                        return faker()->email();
                    case 'Symfony\Component\Validator\Constraints\Url';
                        return faker()->url();
                }
        }

        // try to Generate string from fieldname
        // roles, company, email, password, url, firstName, name|lastName, title, address, city, country, isbn, ....
        if (array_key_exists($fieldName, StringScalarDetectionList::SCALAR_DETECTION_LIST)) {
            return faker()->{StringScalarDetectionList::SCALAR_DETECTION_LIST[$fieldName]}();
        }

        return faker()->sentence();
    }

    /**
     * if we found some fieldName we know then we create it with faker
     *
     * @return string[]
     */
    public function createJsonPropertyFromAnnotationOrFieldName(array $propertyAnnotations, $fieldName): array
    {
        // for field roles as examble return ['ROLE_USER']
        if (array_key_exists($fieldName, JsonDetectionList::LIST)) {
            return JsonDetectionList::LIST[$fieldName];
        }

        return ['DEFAULT'];
    }

    /**
     * @TODO
     * We store defaults values from properties.
     * if there is an default property which is not null|''|false we can use it? $this->defaultProperties
     */
    public function getDefaultFromProperty(ReflectionClass $reflectionClass): void
    {
        $this->defaultProperties = $reflectionClass->getDefaultProperties();
    }

    /**
     * @TODO get relations which cant be NULL
     */
    public function getPropertiesFromDoctrineRelations()
    {

    }

    /**
     * @TODO
     * This should be only used via flag/argument .. make:ftg --use-properties-from-factory
     * A Factory should have all nesseccary fields or in other words we cant guarantee thats there are all needed fields
     * If using this flag its up to developer to have defaults filled there
     *
     * @TODO maybe we can add/move this extractor to foundry.
     */
    public function createPropertiesFromFactory()
    {
    }

    /**
     * @return array
     */
    public function getProperties(): array
    {
        return $this->properties;
    }

    /**
     * @param array $properties
     */
    public function setProperties(array $properties): void
    {
        $this->properties = $properties;
    }
}
