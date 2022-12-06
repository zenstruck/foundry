<?php

namespace Zenstruck\Foundry\Bundle\Maker\Factory;

use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * @internal
 */
interface DefaultPropertiesGuesser
{
    public function __invoke(SymfonyStyle $io, MakeFactoryData $makeFactoryData, MakeFactoryQuery $makeFactoryQuery): void;

    public function supports(MakeFactoryData $makeFactoryData): bool;
}
