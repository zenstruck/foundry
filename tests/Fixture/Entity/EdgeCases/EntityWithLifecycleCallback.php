<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Tests\Fixture\Entity\EdgeCases;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
class EntityWithLifecycleCallback
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    public ?int $id = null;

    #[ORM\Column(nullable: true)]
    public ?string $prop1 = null;

    #[ORM\Column(nullable: true)]
    public ?string $prop2 = null;

    #[ORM\PrePersist]
    public function prePersist(): void
    {
        $this->prop1 = 'pre-persist';

        if ($this->prop2 === null) {
            throw new \LogicException('prop2 should not be empty');
        }
    }
}
