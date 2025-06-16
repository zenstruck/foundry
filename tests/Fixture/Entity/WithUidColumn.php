<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Tests\Fixture\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;
use Zenstruck\Foundry\Tests\Fixture\Model\Base;

#[ORM\Entity]
class WithUidColumn extends Base
{
    #[ORM\Column(type: Types::GUID)]
    private string $guid;

    #[ORM\Column(type: 'uuid')]
    private Uuid $uuid;

    public function __construct(string $guid, Uuid $uuid)
    {
        $this->guid = $guid;
        $this->uuid = $uuid;
    }

    public function getGuid(): string
    {
        return $this->guid;
    }

    public function setGuid(string $guid): static
    {
        $this->guid = $guid;

        return $this;
    }

    public function getUuid(): Uuid
    {
        return $this->uuid;
    }

    public function setUuid(Uuid $uuid): static
    {
        $this->uuid = $uuid;

        return $this;
    }
}
