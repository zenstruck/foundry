<?php

declare(strict_types=1);

namespace Zenstruck\Foundry\Utils\Rector\Tests\Fixtures;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity()]
class DummyPersistentObject
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    public int|null $id;
}
