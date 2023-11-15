<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Bundle\Maker\Factory;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\Mapping\ClassMetadata;

/**
 * @internal
 */
abstract class AbstractDoctrineDefaultPropertiesGuesser extends AbstractDefaultPropertyGuesser
{
    public function __construct(protected ManagerRegistry $managerRegistry, FactoryClassMap $factoryClassMap, FactoryGenerator $factoryGenerator)
    {
        parent::__construct($factoryClassMap, $factoryGenerator);
    }

    protected function getClassMetadata(MakeFactoryData $makeFactoryData): ClassMetadata
    {
        $class = $makeFactoryData->getObjectFullyQualifiedClassName();

        foreach ($this->managerRegistry->getManagers() as $manager) {
            try {
                $classMetadata = $manager->getClassMetadata($class);
            } catch (\Throwable) {
            }
        }

        if (!isset($classMetadata)) {
            throw new \InvalidArgumentException("\"{$class}\" is not a valid Doctrine class name.");
        }

        return $classMetadata;
    }
}
