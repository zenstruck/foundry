<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Tests\Fixture\Stories;

use Symfony\Component\Routing\RouterInterface;
use Zenstruck\Foundry\Story;

/**
 * A story with dependencies which is not registered as a service in the test kernel.
 */
final class UnregisteredServiceStory extends Story
{
    public function __construct(
        private readonly RouterInterface $router,
    ) {
    }

    public function build(): void
    {
        $this->addState('host', $this->router->getContext()->getHost());
    }
}
