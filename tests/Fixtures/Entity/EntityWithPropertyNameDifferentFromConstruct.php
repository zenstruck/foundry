<?php

namespace Zenstruck\Foundry\Tests\Fixtures\Entity;

use Doctrine\ORM\Mapping as ORM;
use Zenstruck\Foundry\Tests\Fixtures\Object\SomeObject;

#[ORM\Entity]
#[ORM\Table(name: "entity_with_property_name_different_from_construct")]
class EntityWithPropertyNameDifferentFromConstruct
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private $id;

    #[ORM\ManyToOne(targetEntity: EntityForRelations::class)]
    private EntityForRelations $entity;

    #[ORM\Column()]
    private string $someField;

    #[ORM\Embedded(Address::class)]
    private Address $address;

    private SomeObject $someObject;

    public function __construct(string $scalar, EntityForRelations $relationship, Address $embedded, SomeObject $notPersistedObject)
    {
        $this->someField = $scalar;
        $this->entity = $relationship;
        $this->address = $embedded;
        $this->someObject = $notPersistedObject;
    }
}
