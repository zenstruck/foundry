<?php

declare(strict_types=1);

namespace Integration\ORM;

use Zenstruck\Foundry\Tests\Fixture\Factories\Entity\GenericProxyEntityFactory;
use Zenstruck\Foundry\Tests\Integration\Persistence\GenericRepositoryDecoratorTestCase;
use Zenstruck\Foundry\Tests\Integration\RequiresORM;

final class GenericProxyEntityRepositoryDecoratorTest extends GenericRepositoryDecoratorTestCase
{
    use RequiresORM;

    protected function factory(): GenericProxyEntityFactory // @phpstan-ignore-line
    {
        return GenericProxyEntityFactory::new();
    }
}
