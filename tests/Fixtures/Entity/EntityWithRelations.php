<?php

namespace Zenstruck\Foundry\Tests\Fixtures\Entity;

use Doctrine\ORM\Mapping as ORM;
use Zenstruck\Foundry\Tests\Fixtures\Entity\Cascade\Brand;

/**
 * @ORM\Entity
 * @ORM\Table(name="entity_with_relations")
 */
class EntityWithRelations
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\OneToOne(targetEntity=Category::class, cascade={"persist", "remove"})
     * @ORM\JoinColumn(nullable=false)
     */
    private $oneToOne;

    /**
     * @ORM\OneToOne(targetEntity=Category::class, cascade={"persist", "remove"})
     * @ORM\JoinColumn(nullable=true)
     */
    private $oneToOneNullable;

    /**
     * @ORM\ManyToOne(targetEntity=Category::class)
     * @ORM\JoinColumn(nullable=false)
     */
    private $manyToOne;

    /**
     * @ORM\ManyToOne(targetEntity=Category::class)
     * @ORM\JoinColumn(nullable=true)
     */
    private $manyToOneNullable;

    /**
     * @ORM\ManyToOne(targetEntity=Category::class)
     */
    private $manyToOneNullableDefault;

    /**
     * @ORM\ManyToMany(targetEntity=Category::class)
     */
    private $manyToMany;

    /**
     * @ORM\ManyToOne(targetEntity=Brand::class)
     * @ORM\JoinColumn(nullable=false)
     */
    private $manyToOneWithNotExistingFactory;
}
