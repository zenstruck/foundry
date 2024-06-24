<?php

declare(strict_types=1);

namespace Zenstruck\Foundry\Tests\Fixture\Entity\EdgeCases\RelationshipWithGlobalEntity;

use Doctrine\ORM\Mapping as ORM;
use Zenstruck\Foundry\Tests\Fixture\Entity\GlobalEntity;

/**
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 */
#[ORM\MappedSuperclass]
abstract class RelationshipWithGlobalEntity
{
    #[ORM\Id]
    #[ORM\Column]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    public ?int $id = null;

    protected ?GlobalEntity $globalEntity = null;

    public function setGlobalEntity(?GlobalEntity $globalEntity): void
    {
        $this->globalEntity = $globalEntity;
    }

    public function getGlobalEntity(): ?GlobalEntity
    {
        return $this->globalEntity;
    }
}
