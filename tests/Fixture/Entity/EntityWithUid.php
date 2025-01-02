<?php

declare(strict_types=1);

namespace Zenstruck\Foundry\Tests\Fixture\Entity;

use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;
use Doctrine\ORM\Mapping as ORM;

/**
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 */
#[ORM\Entity]
class EntityWithUid
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    public Uuid $id;

    public function __construct()
    {
        $this->id = Uuid::v7();
    }
}
