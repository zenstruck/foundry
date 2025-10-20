<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Tests\Fixture\App\Controller;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Zenstruck\Foundry\Tests\Fixture\Document\GenericDocument;
use Zenstruck\Foundry\Tests\Fixture\Entity\GenericEntity;

#[AsController]
final class UpdateGenericModel
{
    #[Route('/orm/update/{id}/{newValue}')]
    public function ormUpdate(EntityManagerInterface $entityManager, int $id, string $newValue = 'foo'): Response
    {
        $genericEntity = $entityManager->find(GenericEntity::class, $id);
        $genericEntity?->setProp1($newValue);
        $entityManager->flush();

        return new Response();
    }

    #[Route('/mongo/update/{id}/{newValue}')]
    public function mongoUpdate(DocumentManager $entityManager, int $id, string $newValue = 'foo'): Response
    {
        $genericDocument = $entityManager->find(GenericDocument::class, $id);
        $genericDocument?->setProp1($newValue);
        $entityManager->flush();

        return new Response();
    }
}
