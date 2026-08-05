<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Test\Behat;

use Behat\Gherkin\Node\TableNode;
use Behat\Step\Given;
use Zenstruck\Foundry\ObjectFactory;

/**
 * Built-in "Given" steps creating Foundry objects.
 *
 * The composing class only needs to implement FoundryContextInterface; the dependencies are
 * injected by the HasFactoryShortNameResolver and HasObjectRegistry traits (see FoundryCreationContext).
 *
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 * @experimental
 */
trait CreationSteps
{
    use HasFactoryShortNameResolver;
    use HasObjectRegistry;
    use HasTableParametersNormalizer;

    #[Given('there is a(n) :factoryShortName')]
    #[Given('there is a(n) :factoryShortName named :objectName')]
    public function createObject(string $factoryShortName, ?string $objectName = null): void
    {
        $this->resolveFactory($factoryShortName, $objectName)->create();
    }

    #[Given('there is a(n) :factoryShortName with:')]
    #[Given('there is a(n) :factoryShortName named :objectName with:')]
    public function createObjectWithProperties(TableNode $table, string $factoryShortName, ?string $objectName = null): void
    {
        $factory = $this->resolveFactory($factoryShortName, $objectName);
        $parametersList = $this->tableParametersNormalizer->normalize($table, $factoryShortName);

        if (1 !== \count($parametersList)) {
            throw new \InvalidArgumentException(\sprintf('Expected exactly one line of properties to create one object, got %d lines. Use "there are %s with:" to create multiple objects.', \count($parametersList), $factoryShortName));
        }

        $factory->create($parametersList[0]);
    }

    #[Given('there are :factoryShortName with:')]
    public function createObjectsWithProperties(TableNode $table, string $factoryShortName): void
    {
        $parametersList = $this->tableParametersNormalizer->normalize($table, $factoryShortName);

        foreach ($parametersList as $parameters) {
            $objectName = $parameters['_ref'] ?? null;
            unset($parameters['_ref']);

            $this->resolveFactory($factoryShortName, $objectName)
                ->create($parameters);
        }
    }

    /**
     * @return ObjectFactory<object>
     */
    private function resolveFactory(string $factoryShortName, ?string $objectName = null): ObjectFactory
    {
        $factory = $this->factoryResolver->factoryFor($factoryShortName);

        // an empty name (e.g. an empty "_ref" cell) means the object is intentionally unnamed,
        // but falsy names like "0" are legitimate references
        if (null === $objectName || '' === $objectName) {
            return $factory;
        }

        return $factory->afterInstantiate(
            fn(object $object) => $this->objectRegistry->store($object, $objectName)
        );
    }
}
