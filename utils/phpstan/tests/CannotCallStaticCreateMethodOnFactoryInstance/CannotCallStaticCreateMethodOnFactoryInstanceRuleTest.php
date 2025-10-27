<?php

namespace Zenstruck\Foundry\Utils\PHPStan\Tests;

use PHPStan\Testing\RuleTestCase;
use Zenstruck\Foundry\Utils\PHPStan\CannotCallStaticCreateMethodOnFactoryInstanceRule;
use PHPStan\Rules\Rule;

/**
 * @extends RuleTestCase<CannotCallStaticCreateMethodOnFactoryInstanceRule>
 */
final class CannotCallStaticCreateMethodOnFactoryInstanceRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new CannotCallStaticCreateMethodOnFactoryInstanceRule();
    }

    public function testInvalidCalls(): void
    {
        $this->analyse([
            __DIR__ . '/Fixtures/invalid.php.inc',
        ], [
            [
                'Method "createOne()" should not be called on an instance.
    💡 Call the method statically instead: "SomeFactory::createOne()", or use "$someFactory->create()" if you want to call the method on the instance.',
                6,
            ],
            [
                'Method "createMany()" should not be called on an instance.
    💡 Call the method statically instead: "SomeFactory::createMany()", or use "$someFactory->many()->create()" if you want to call the method on the instance.',
                7,
            ],
            [
                'Method "createRange()" should not be called on an instance.
    💡 Call the method statically instead: "SomeFactory::createRange()", or use "$someFactory->range()->create()" if you want to call the method on the instance.',
                8,
            ],
            [
                'Method "createSequence()" should not be called on an instance.
    💡 Call the method statically instead: "SomeFactory::createSequence()", or use "$someFactory->sequence()->create()" if you want to call the method on the instance.',
                9,
            ],
            [
                'Method "createOne()" should not be called on an instance.
    💡 Call the method statically instead: "SomeFactory::createOne()", or use "$someFactory->create()" if you want to call the method on the instance.',
                10,
            ],
        ]);
    }

    public function testValidCalls(): void
    {
        $this->analyse([
            __DIR__ . '/Fixtures/valid.php.inc',
        ], []);
    }
}
