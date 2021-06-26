<?php

namespace Zenstruck\Foundry\Proxy;

use ProxyManager\Factory\AccessInterceptorValueHolderFactory;
use ProxyManager\ProxyGenerator\ProxyGeneratorInterface;

/**
 * A little extension to add {@see SetValueHolderMethod} to the proxy.
 *
 * @author Wouter de Jong <wouter@wouterj.nl>
 */
class ValueReplacingAccessInterceptorValueHolderFactory extends AccessInterceptorValueHolderFactory
{
    protected function getGenerator(): ProxyGeneratorInterface
    {
        return new ValueReplacingGenerator(parent::getGenerator());
    }
}
