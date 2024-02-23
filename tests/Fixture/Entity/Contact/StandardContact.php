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
use Zenstruck\Foundry\Tests\Fixture\Entity\Address\StandardAddress;
use Zenstruck\Foundry\Tests\Fixture\Entity\Category;
use Zenstruck\Foundry\Tests\Fixture\Entity\Category\StandardCategory;
use Zenstruck\Foundry\Tests\Fixture\Entity\Contact;
use Zenstruck\Foundry\Tests\Fixture\Entity\Tag\StandardTag;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
#[ORM\Entity]
#[ORM\Table(name: 'posts')]
#[ORM\InheritanceType(value: 'SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'type')]
#[ORM\DiscriminatorMap(['simple' => StandardContact::class, 'specific' => ChildContact::class])]
class StandardContact extends Contact
{
    #[ORM\ManyToOne(targetEntity: StandardCategory::class, inversedBy: 'contacts')]
    #[ORM\JoinColumn(nullable: true)]
    protected Category|null $category = null;

    #[ORM\ManyToOne(targetEntity: StandardCategory::class, inversedBy: 'secondaryContacts')]
    protected Category|null $secondaryCategory = null;

    #[ORM\ManyToMany(targetEntity: StandardTag::class, inversedBy: 'contacts')]
    protected Collection $tags;

    #[ORM\ManyToMany(targetEntity: StandardTag::class, inversedBy: 'secondaryContacts')]
    #[ORM\JoinTable(name: 'category_tag_standard_secondary')]
    protected Collection $secondaryTags;

    #[ORM\OneToOne(targetEntity: StandardAddress::class)]
    #[ORM\JoinColumn(nullable: false)]
    protected Address $address;
}
