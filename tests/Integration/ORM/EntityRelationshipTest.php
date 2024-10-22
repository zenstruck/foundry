<?php

declare(strict_types=1);

namespace Zenstruck\Foundry\Tests\Integration\ORM;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Persistence\PersistenceManager;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;
use Zenstruck\Foundry\Tests\Fixture\DoctrineCascadeRelationship\UsingRelationShips;
use Zenstruck\Foundry\Tests\Fixture\DoctrineCascadeRelationship\WithEntityRelationShip;
use Zenstruck\Foundry\Tests\Fixture\Entity\Category\StandardCategory;
use Zenstruck\Foundry\Tests\Fixture\Entity\Contact\StandardContact;
use Zenstruck\Foundry\Tests\Fixture\Factories\Entity\Category\StandardCategoryFactory;
use Zenstruck\Foundry\Tests\Fixture\Factories\Entity\Contact\StandardContactFactory;

/**
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 */
final class EntityRelationshipTest extends KernelTestCase
{
    use WithEntityRelationShip, Factories, ResetDatabase;

    /**
     * @test
     * @dataProvider provideCascadeRelationshipsCombination
     */
    #[UsingRelationShips(StandardContact::class, ['category'])]
    #[UsingRelationShips(StandardCategory::class, ['contacts'])]
    public function many_to_one(): void
    {
        $contact = StandardContactFactory::new()::createOne([
            'category' => StandardCategoryFactory::new(),
        ]);

        StandardContactFactory::repository()->assert()->count(1);
        StandardCategoryFactory::repository()->assert()->count(1);

        $this->assertNotNull($contact->id);
        $this->assertNotNull($contact->getCategory()?->id);
    }
}
