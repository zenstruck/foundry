<?php

declare(strict_types=1);

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Tests\Fixture\Entity\EdgeCases\IndexedByJoinColumn;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Zenstruck\Foundry\Tests\Fixture\Model\Base;

#[ORM\Entity]
#[ORM\Table('indexed_by_join_column_product')]
class Product extends Base
{
    /** @var Collection<string, ProductTranslation> */
    #[ORM\OneToMany(targetEntity: ProductTranslation::class, mappedBy: 'product', cascade: ['persist'], indexBy: 'languageId')]
    private Collection $translations;

    public function __construct()
    {
        $this->translations = new ArrayCollection();
    }

    /**
     * @return Collection<string, ProductTranslation>
     */
    public function getTranslations(): Collection
    {
        return $this->translations;
    }

    public function addTranslation(ProductTranslation $translation): void
    {
        if (!$this->translations->contains($translation)) {
            $this->translations->add($translation);
        }
    }

    public function removeTranslation(ProductTranslation $translation): void
    {
        $this->translations->removeElement($translation);
    }
}
