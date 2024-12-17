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

namespace Zenstruck\Foundry\Tests\Fixture\EntityInAnotherSchema;

use Doctrine\ORM\Mapping as ORM;
use Zenstruck\Foundry\Tests\Fixture\Model\Base;

/**
 * Create custom "cms" schema ({@see Article}) to ensure "migrate" mode is still working with multiple schemas.
 * Note: this entity is added to mapping only for PostgreSQL, as it is the only supported DBMS which handles multiple schemas.
 *
 * @see https://github.com/zenstruck/foundry/issues/618
 */
#[ORM\Entity]
#[ORM\Table(name: 'article', schema: 'cms')]
class Article extends Base
{
    #[ORM\Column(length: 255)]
    private string $title;

    public function __construct(string $title)
    {
        $this->title = $title;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }
}
