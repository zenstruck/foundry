<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Maker\Factory;

use Doctrine\Persistence\Mapping\ClassMetadata;
use Zenstruck\Foundry\Persistence\PersistenceManager;

/**
 * @internal
 */
abstract class AbstractDoctrineDefaultPropertiesGuesser extends AbstractDefaultPropertyGuesser
{
    public function __construct(protected PersistenceManager $persistenceManager, FactoryClassMap $factoryClassMap, FactoryGenerator $factoryGenerator)
    {
        parent::__construct($factoryClassMap, $factoryGenerator);
    }

    protected function getClassMetadata(MakeFactoryData $makeFactoryData): ClassMetadata
    {
        $class = $makeFactoryData->getObjectFullyQualifiedClassName();

        return $this->persistenceManager->metadataFor($class);
    }
}
