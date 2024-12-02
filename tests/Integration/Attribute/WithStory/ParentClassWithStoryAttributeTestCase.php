<?php

declare(strict_types=1);

namespace Zenstruck\Foundry\Tests\Integration\Attribute\WithStory;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Attribute\WithStory;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;
use Zenstruck\Foundry\Tests\Fixture\Stories\EntityStory;
use Zenstruck\Foundry\Tests\Integration\RequiresORM;

/**
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 */
#[WithStory(EntityStory::class)]
abstract class ParentClassWithStoryAttributeTestCase extends KernelTestCase
{
    use Factories, ResetDatabase, RequiresORM;
}
