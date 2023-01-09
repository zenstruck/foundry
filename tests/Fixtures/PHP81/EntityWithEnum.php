<?php

declare(strict_types=1);

namespace Zenstruck\Foundry\Tests\Fixtures\PHP81;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="entity_with_enum")
 */
class EntityWithEnum
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    public $id;

    /**
     * @ORM\Column(enumType=SomeEnum::class)
     */
    public SomeEnum $enum;
}
