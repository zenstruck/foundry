<?php

declare(strict_types=1);

namespace Zenstruck\Foundry\Utils\Rector;

use PHPStan\Type\Doctrine\ObjectMetadataResolver;
use Rector\Config\RectorConfig;

final class RuleRequirementsChecker
{
    public static function checkRequirements(): void
    {
        if (!method_exists(RectorConfig::class, 'enableCollectors')) {
            throw new \RuntimeException(
                'Foundry\'s Rector rules need package rector/rector to be at least at version 0.18. Please update it with command "composer update rector/rector"'
            );
        }

        if (!class_exists(ObjectMetadataResolver::class)) {
            throw new \RuntimeException(
                'Foundry\'s Rector rules need package phpstan/phpstan-doctrine. Please install it with command "composer require phpstan/phpstan-doctrine --dev"'
            );
        }
    }
}
