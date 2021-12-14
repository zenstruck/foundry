<?php

namespace Zenstruck\Foundry\Tests\Fixtures\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="foos")
 */
class Foo
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
     * @ORM\OneToMany(targetEntity=Category::class, mappedBy="foo")
     */
    private $oneToMany;

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
}
