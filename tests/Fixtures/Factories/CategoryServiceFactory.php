<?php

namespace Zenstruck\Foundry\Tests\Fixtures\Factories;

use Zenstruck\Foundry\Persistence\PersistentObjectFactory;
use Zenstruck\Foundry\Tests\Fixtures\Entity\Category;
use Zenstruck\Foundry\Tests\Fixtures\Service;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class CategoryServiceFactory extends PersistentObjectFactory
{
    public function __construct(private Service $service)
    {
        parent::__construct();
    }

    public static function class(): string
    {
        return Category::class;
    }

    protected function getDefaults(): array
    {
        return ['name' => $this->service->name];
    }
}
