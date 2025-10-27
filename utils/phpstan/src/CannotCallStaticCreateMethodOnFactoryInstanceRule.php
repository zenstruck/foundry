<?php

namespace Zenstruck\Foundry\Utils\PHPStan;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ObjectType;
use Zenstruck\Foundry\Factory;

/**
 * @implements Rule<Node\Expr\StaticCall>
 */
final class CannotCallStaticCreateMethodOnFactoryInstanceRule implements Rule
{
    private const STATIC_METHODS = [
        'createOne' => 'create()',
        'createMany' => 'many()->create()',
        'createRange' => 'range()->create()',
        'createSequence' => 'sequence()->create()',
    ];

    public function getNodeType(): string
    {
        return Node\Expr\StaticCall::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        if (!$node->name instanceof Node\Identifier
            || !$node->class instanceof Node\Expr) {
            return [];
        }

        $type = $scope->getType($node->class);

        $methodName = $node->name->toString();

        if (
            !\in_array($methodName, array_keys(self::STATIC_METHODS), true)
            || !$type->isObject()->yes()
            || !(new ObjectType(Factory::class))->accepts($type, true)->yes()
        ) {
            return [];
        }

        return [
            RuleErrorBuilder::message(
                sprintf(
                    'Method "%s()" should not be called on an instance.',
                    $methodName,
                )
            )
                ->tip(
                    sprintf(
                        'Call the method statically instead: "SomeFactory::%s()", or use "$someFactory->%s" if you want to call the method on the instance.',
                        $methodName,
                        self::STATIC_METHODS[$methodName]
                    )
                )
                ->identifier('foundry.staticMethodCalledOnInstance')
                ->build(),
        ];
    }
}
