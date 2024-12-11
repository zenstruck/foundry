<?php

declare(strict_types=1);

namespace Zenstruck\Foundry\Tests\Fixture\DoctrineCascadeRelationship;

use PHPUnit\Event;
use PHPUnit\Runner;
use PHPUnit\TextUI;

/**
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 */
final class PhpUnitTestExtension implements Runner\Extension\Extension, Event\Test\DataProviderMethodCalledSubscriber
{
    public function bootstrap(
        TextUI\Configuration\Configuration $configuration,
        Runner\Extension\Facade $facade,
        Runner\Extension\ParameterCollection $parameters,
    ): void {
        $facade->registerSubscribers($this);
    }

    public function notify(Event\Test\DataProviderMethodCalled $event): void
    {
        $testMethod = $event->testMethod();

        $attributes = (new \ReflectionMethod($testMethod->className(), $testMethod->methodName()))->getAttributes(UsingRelationships::class);

        if (!$attributes) {
            return;
        }

        if (!method_exists($testMethod->className(), 'setCurrentProvidedMethodName')) {
            throw new \LogicException("Test \"{$testMethod->className()}::{$testMethod->methodName()}()\" should use trait ChangesEntityRelationshipCascadePersist.");
        }

        $testMethod->className()::setCurrentProvidedMethodName($testMethod->methodName());
    }
}
