<?php

namespace Zenstruck\Foundry\Bundle\Extractor;

use Doctrine\ORM\EntityManagerInterface;
use Exception;
use ReflectionClass;

class Property
{
    /**
     * @var array
     */
    private $properties = [];

    /**
     * @var array
     */
    private $defaultProperties = [];

    /**
     * @var EntityManagerInterface
     */
    private $em;

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
     */
    public function getFakerMethodFromDoctrineFieldMappings(ReflectionClass $entity): array
    {
        $classMetaData = $this->em->getClassMetadata($entity->getName());
        $this->getDefaultFromProperty($entity);
        $identifierFieldNames = $classMetaData->getIdentifierFieldNames();

        foreach ($classMetaData->fieldMappings as $property) {
            // IGNORE FIELD IF IDENTIFIER
            if (\in_array($property['fieldName'], $identifierFieldNames)) {
                continue;
            }

            // CREATE FROM DOCTRINE TYPE IF PROP IS NOT NULLABLE
            if (!$property['nullable']) {
                $this->properties[$property['fieldName']] = $this->createFakerMethodFromDoctrineType($property['type']);
            }
        }

        return $this->properties;
    }

    /**
     * @throws Exception
     */
    public function createFakerMethodFromDoctrineType(string $doctrineType): string
    {
        $doctrineType = \mb_strtoupper($doctrineType);

        if (\array_key_exists($doctrineType, DoctrineTypes::DOCTRINE_TYPES)) {
            return DoctrineTypes::DOCTRINE_TYPES[$doctrineType];
        }

        throw new Exception('DOCTRINE_TYPE not found: '.$doctrineType);
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

    public function getProperties(): array
    {
        return $this->properties;
    }

    public function setProperties(array $properties): void
    {
        $this->properties = $properties;
    }
}
