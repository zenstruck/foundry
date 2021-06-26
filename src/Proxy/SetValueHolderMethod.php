<?php

namespace Zenstruck\Foundry\Proxy;

use Laminas\Code\Generator\ParameterGenerator;
use Laminas\Code\Generator\PropertyGenerator;
use ProxyManager\Generator\MethodGenerator;

/**
 * Adds a setter method for the proxied value holder.
 *
 * The ProxyManager doesn't allow replacing the value holder, but this is
 * required if Foundry is used across Symfony kernel reboots (which is
 * common in HTTP tests).
 *
 * @author Wouter de Jong <wouter@wouterj.nl>
 */
class SetValueHolderMethod extends MethodGenerator
{
    public function __construct(PropertyGenerator $valueHolderProperty)
    {
        parent::__construct('setWrappedValueHolder');

        $this->setParameter(new ParameterGenerator('wrappedValueHolder'));
        $this->setReturnType('void');
        $this->setBody('$this->'.$valueHolderProperty->getName().' = $wrappedValueHolder;');
    }
}
