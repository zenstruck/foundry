<?php

declare(strict_types=1);

namespace Zenstruck\Foundry\Bundle\Maker\Factory\Exception;

final class FactoryClassAlreadyExistException extends \InvalidArgumentException
{
    public function __construct(string $factoryClass)
    {
        parent::__construct("Factory \"{$factoryClass}\" already exists.");
    }
}
