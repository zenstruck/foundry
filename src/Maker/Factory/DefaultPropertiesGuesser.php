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

use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * @internal
 */
interface DefaultPropertiesGuesser
{
    public function __invoke(SymfonyStyle $io, MakeFactoryData $makeFactoryData, MakeFactoryQuery $makeFactoryQuery): void;

    public function supports(MakeFactoryData $makeFactoryData): bool;
}
