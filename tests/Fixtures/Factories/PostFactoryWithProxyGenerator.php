<?php

namespace Zenstruck\Foundry\Tests\Fixtures\Factories;

/**
 * @author Wouter de Jong <wouter@wouterj.nl>
 */
class PostFactoryWithProxyGenerator extends PostFactory
{
    protected function initialize(): self
    {
        return parent::initialize()->withProxyGenerator();
    }
}
