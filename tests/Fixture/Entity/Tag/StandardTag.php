<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Tests\Fixture\Entity\Tag;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Zenstruck\Foundry\Tests\Fixture\Entity\Contact\StandardContact;
use Zenstruck\Foundry\Tests\Fixture\Entity\Tag;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
#[ORM\Entity]
class StandardTag extends Tag
{
    #[ORM\ManyToMany(targetEntity: StandardContact::class, mappedBy: 'tags')]
    protected Collection $contacts;

    #[ORM\ManyToMany(targetEntity: StandardContact::class, mappedBy: 'secondaryTags')]
    protected Collection $secondaryContacts;
}
