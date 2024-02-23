<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Tests\Fixture\Entity\Category;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Zenstruck\Foundry\Tests\Fixture\Entity\Category;
use Zenstruck\Foundry\Tests\Fixture\Entity\Contact\StandardContact;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
#[ORM\Entity]
class StandardCategory extends Category
{
    #[ORM\OneToMany(mappedBy: 'category', targetEntity: StandardContact::class)]
    protected Collection $contacts;

    #[ORM\OneToMany(mappedBy: 'secondaryCategory', targetEntity: StandardContact::class)]
    protected Collection $secondaryContacts;
}
