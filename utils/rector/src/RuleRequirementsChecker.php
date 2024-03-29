<?php

declare(strict_types=1);

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Utils\Rector;

use PHPStan\Type\Doctrine\ObjectMetadataResolver;
use Rector\Rector\AbstractRector;

final class RuleRequirementsChecker
{
    public static function checkRequirements(): void
    {
        if (!\class_exists(AbstractRector::class)) {
            throw new \RuntimeException('Foundry\'s Rector rules need package rector/rector to be at least at version 1.0. Please update it with command "composer update rector/rector"');
        }

        if (!\class_exists(ObjectMetadataResolver::class)) {
            throw new \RuntimeException('Foundry\'s Rector rules need package phpstan/phpstan-doctrine. Please install it with command "composer require phpstan/phpstan-doctrine --dev"');
        }
    }
}
