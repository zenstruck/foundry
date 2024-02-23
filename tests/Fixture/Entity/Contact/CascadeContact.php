<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Tests\Fixture\Entity\Contact;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Zenstruck\Foundry\Tests\Fixture\Entity\Address;
use Zenstruck\Foundry\Tests\Fixture\Entity\Address\CascadeAddress;
use Zenstruck\Foundry\Tests\Fixture\Entity\Category;
use Zenstruck\Foundry\Tests\Fixture\Entity\Category\CascadeCategory;
use Zenstruck\Foundry\Tests\Fixture\Entity\Contact;
use Zenstruck\Foundry\Tests\Fixture\Entity\Tag\CascadeTag;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
#[ORM\Entity]
class CascadeContact extends Contact
{
    #[ORM\ManyToOne(targetEntity: Category\CascadeCategory::class, cascade: ['persist', 'remove'], inversedBy: 'contacts')]
    #[ORM\JoinColumn(nullable: true)]
    protected Category|null $category = null;

    #[ORM\ManyToOne(targetEntity: CascadeCategory::class, cascade: ['persist', 'remove'], inversedBy: 'secondaryContacts')]
    protected Category|null $secondaryCategory = null;

    #[ORM\ManyToMany(targetEntity: CascadeTag::class, inversedBy: 'contacts', cascade: ['persist', 'remove'])]
    protected Collection $tags;

    #[ORM\ManyToMany(targetEntity: CascadeTag::class, inversedBy: 'secondaryContacts', cascade: ['persist', 'remove'])]
    #[ORM\JoinTable(name: 'category_tag_cascade_secondary')]
    protected Collection $secondaryTags;

    #[ORM\OneToOne(targetEntity: CascadeAddress::class, cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    protected Address $address;
}
