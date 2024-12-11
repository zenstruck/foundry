<?php

declare(strict_types=1);

namespace Zenstruck\Foundry\Tests;

use PHPUnit\Runner;
use PHPUnit\TextUI;
use Zenstruck\Foundry\Tests\Fixture\DoctrineCascadeRelationship\CascadeRelationshipOnDataProviderCalledSubscriber;

/**
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 */
final class PhpUnitTestExtension implements Runner\Extension\Extension
{
    public function bootstrap(
        TextUI\Configuration\Configuration $configuration,
        Runner\Extension\Facade $facade,
        Runner\Extension\ParameterCollection $parameters,
    ): void {
        $facade->registerSubscribers(new CascadeRelationshipOnDataProviderCalledSubscriber());
    }
}
