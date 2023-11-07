<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Tests\Fixtures\Factories;

use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;
use Zenstruck\Foundry\Tests\Fixtures\Entity\Category;
use Zenstruck\Foundry\Tests\Fixtures\Service;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class CategoryServiceFactory extends PersistentProxyObjectFactory
{
    public function __construct(private Service $service)
    {
        parent::__construct();
    }

    public static function class(): string
    {
        return Category::class;
    }

    protected function defaults(): array|callable
    {
        return ['name' => $this->service->name];
    }
}
