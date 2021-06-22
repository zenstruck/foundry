<?php

namespace Zenstruck\Foundry\Proxy;

use Laminas\Code\Generator\ClassGenerator;
use ProxyManager\ProxyGenerator\LazyLoadingValueHolder\PropertyGenerator\ValueHolderProperty;
use ProxyManager\ProxyGenerator\ProxyGeneratorInterface;
use ReflectionClass;

/**
 * A generator decorator to add {@see SetValueHolderMethod}.
 *
 * @author Wouter de Jong <wouter@wouterj.nl>
 */
class ValueReplacingGenerator implements ProxyGeneratorInterface
{
    /** @var ProxyGeneratorInterface */
    private $generator;

    public function __construct(ProxyGeneratorInterface $generator)
    {
        $this->generator = $generator;
    }

    public function generate(ReflectionClass $originalClass, ClassGenerator $classGenerator): void
    {
        $this->generator->generate($originalClass, $classGenerator);

        $valueHolderProperty = null;
        foreach ($classGenerator->getProperties() as $property) {
            if ($property instanceof ValueHolderProperty) {
                $valueHolderProperty = $property;

                break;
            }
        }
        \assert($valueHolderProperty instanceof ValueHolderProperty);

        $classGenerator->addMethodFromGenerator(new SetValueHolderMethod($valueHolderProperty));
    }
}
