<?php

namespace Zenstruck\Foundry\Tests\Fixtures\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class PostRepository extends EntityRepository
{
    public function customMethod(): string
    {
        return 'from custom method';
    }
}
