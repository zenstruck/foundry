<?php

namespace Zenstruck\Foundry\Bundle\Maker\Factory;

/**
 * @internal
 */
interface DefaultPropertiesGuesser
{
    public function __invoke(MakeFactoryData $makeFactoryData, bool $allFields): void;

    public function supports(MakeFactoryData $makeFactoryData): bool;
}
