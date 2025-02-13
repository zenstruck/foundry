<?php

declare(strict_types=1);

namespace Zenstruck\Foundry\Tests\Fixture\Entity\EdgeCases\IndexedOneToMany;

use Doctrine\ORM\Mapping as ORM;
use Zenstruck\Foundry\Tests\Fixture\Model\Base;

/**
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 */
#[ORM\Entity]
#[ORM\Table('index_by_one_to_many_level_1')]
class Child extends Base
{
    public function __construct(
        #[ORM\ManyToOne(inversedBy: 'items')]
        #[ORM\JoinColumn(nullable: false)]
        public ParentEntity $parent,

        #[ORM\Column()]
        public string $language,
    ) {
    }
}
