<?php

namespace Zenstruck\Foundry\Tests\Fixtures\Factories;

use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Tests\Fixtures\Entity\Category;
use Zenstruck\Foundry\Tests\Fixtures\Service;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class CategoryServiceFactory extends ModelFactory
{
    private $service;

    public function __construct(Service $service)
    {
        parent::__construct();

        $this->service = $service;
    }

    protected static function getClass(): string
    {
        return Category::class;
    }

    protected function getDefaults(): array
    {
        return ['name' => $this->service->name];
    }
}
