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
final class DeleteGenericModel
{
    #[Route('/orm/delete/{id}')]
    public function ormDelete(EntityManagerInterface $entityManager, int $id): Response
    {
        $genericEntity = $entityManager->find(GenericEntity::class, $id) ?? throw new \RuntimeException('Entity not found');
        $entityManager->remove($genericEntity);
        $entityManager->flush();

        return new Response();
    }

    #[Route('/mongo/delete/{id}')]
    public function mongoDelete(DocumentManager $documentManager, int $id): Response
    {
        $genericDocument = $documentManager->find(GenericDocument::class, $id) ?? throw new \RuntimeException('Document not found');
        $documentManager->remove($genericDocument);
        $documentManager->flush();

        return new Response();
    }

    #[Route('/orm/db/delete/{id}')]
    public function ormDbDelete(EntityManagerInterface $entityManager, int $id): Response
    {
        $entityManager->getConnection()->delete('generic_entity', ['id' => $id]);

        return new Response();
    }

    #[Route('/mongo/db/delete/{id}')]
    public function mongoDbDelete(DocumentManager $documentManager, int $id): Response
    {
        $documentManager->getDocumentCollection(GenericDocument::class)->deleteOne(['_id' => $id]);

        return new Response();
    }
}
