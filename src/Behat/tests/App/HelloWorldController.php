<?php

namespace Zenstruck\Foundry\Behat\Tests\App;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
final class HelloWorldController
{
    #[Route('/')]
    public function index(): Response
    {
        return new Response('Hello World');
    }
}
