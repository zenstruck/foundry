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

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Webmozart\Assert\Assert;
use Zenstruck\Foundry\Tests\Fixture\Entity\Address;
use Zenstruck\Foundry\Tests\Fixture\Entity\Category;
use Zenstruck\Foundry\Tests\Fixture\Entity\Contact;

#[AsController]
final class CreateContact
{
    #[Route('/orm/contacts', methods: 'POST')]
    public function __invoke(Request $request, EntityManagerInterface $entityManager): Response
    {
        $category = $entityManager->find(Category::class, $request->query->getInt('category_id'));
        Assert::notNull($category);

        $address = new Address('city');
        $entityManager->persist($address);

        $contact = new Contact('name', $address);
        $entityManager->persist($contact);

        $contact->setCategory($category);

        $entityManager->flush();

        return new Response();
    }

    #[Route('/hello-world')]
    public function index(): Response
    {
        return new Response('Hello World');
    }
}
