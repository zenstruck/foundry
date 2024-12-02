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

use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\RouterInterface;
use Zenstruck\Foundry\Story;
use Zenstruck\Foundry\Tests\Fixture\Factories\Entity\GenericEntityFactory;

/**
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 */
    final class ServiceStory extends Story
{
    public function __construct(
        private readonly RouterInterface $router
    ) {
    }

    public function build(): void
    {
        $this->addState(
            'foo',
            GenericEntityFactory::createOne(['prop1' => $this->router->getContext()->getHost()])
        );
    }
}
