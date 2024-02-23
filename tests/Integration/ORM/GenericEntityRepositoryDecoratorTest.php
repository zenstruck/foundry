<?php

declare(strict_types=1);

namespace Zenstruck\Foundry\Tests\Integration\ORM;

use Zenstruck\Foundry\Tests\Fixture\Factories\Entity\GenericEntityFactory;
use Zenstruck\Foundry\Tests\Fixture\Factories\GenericModelFactory;
use Zenstruck\Foundry\Tests\Integration\Persistence\GenericRepositoryDecoratorTestCase;
use Zenstruck\Foundry\Tests\Integration\RequiresORM;

final class GenericEntityRepositoryDecoratorTest extends GenericRepositoryDecoratorTestCase
{
    use RequiresORM;

    protected function factory(): GenericModelFactory
    {
        return GenericEntityFactory::new();
    }
}
