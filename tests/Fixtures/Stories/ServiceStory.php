<?php

namespace Zenstruck\Foundry\Tests\Fixtures\Stories;

use Zenstruck\Foundry\Story;
use Zenstruck\Foundry\Tests\Fixtures\Factories\PostFactory;
use Zenstruck\Foundry\Tests\Fixtures\Service;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ServiceStory extends Story
{
    /** @var Service */
    private $service;

    public function __construct(Service $service)
    {
        $this->service = $service;
    }

    public function build(): void
    {
        $this->addState('post', PostFactory::new()->create(['title' => $this->service->name]));
    }
}
