<?php

declare(strict_types=1);

namespace Zenstruck\Foundry\Tests\Fixture\DoctrineCascadeRelationship;

use PHPUnit\Event;
use PHPUnit\Event\Test\DataProviderMethodCalled;

final class CascadeRelationshipOnDataProviderCalledSubscriber implements Event\Test\DataProviderMethodCalledSubscriber
{
    public function notify(DataProviderMethodCalled $event): void
    {
        $testMethod = $event->testMethod();

        $attributes = (new \ReflectionMethod($testMethod->className(), $testMethod->methodName()))->getAttributes(UsingRelationships::class);

        if (!$attributes) {
            return;
        }

        if (!method_exists($testMethod->className(), 'setCurrentProvidedMethodName')) {
            throw new \LogicException("Test \"{$testMethod->className()}::{$testMethod->methodName()}()\" should use trait WithEntityRelationship.");
        }

        $testMethod->className()::setCurrentProvidedMethodName($testMethod->methodName());
    }
}
