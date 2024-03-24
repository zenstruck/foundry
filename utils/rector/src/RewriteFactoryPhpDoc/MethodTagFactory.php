<?php

declare(strict_types=1);

namespace Zenstruck\Foundry\Utils\Rector\RewriteFactoryPhpDoc;

use Doctrine\Persistence\ObjectRepository;
use PHPStan\PhpDocParser\Ast\ConstExpr\ConstExprArrayNode;
use PHPStan\PhpDocParser\Ast\ConstExpr\ConstExprNode;
use PHPStan\PhpDocParser\Ast\ConstExpr\ConstExprStringNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\MethodTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\MethodTagValueParameterNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTagNode;
use PHPStan\PhpDocParser\Ast\Type\GenericTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use Rector\BetterPhpDocParser\ValueObject\Type\BracketsAwareIntersectionTypeNode;
use Rector\BetterPhpDocParser\ValueObject\Type\BracketsAwareUnionTypeNode;
use Rector\BetterPhpDocParser\ValueObject\Type\FullyQualifiedIdentifierTypeNode;
use Rector\BetterPhpDocParser\ValueObject\Type\SpacingAwareArrayTypeNode;
use Zenstruck\Foundry\FactoryCollection;
use Zenstruck\Foundry\Persistence\Proxy;
use Zenstruck\Foundry\Persistence\ProxyRepositoryDecorator;

final class MethodTagFactory
{
    /**
     * @var array<string, array{
     *     static: bool,
     *     returnType: self::RETURN_TYPE_*,
     *     params: array<string, self::PARAM_*>,
     * }>
     */
    public const METHODS = [
        'create' => ['static' => false, 'returnType' => self::RETURN_TYPE_ITEM, 'params' => ['attributes' => self::PARAM_ATTRIBUTES_CALLABLE]],
        'createOne' => ['static' => true, 'returnType' => self::RETURN_TYPE_ITEM, 'params' => ['attributes' => self::PARAM_ATTRIBUTES_DEFAULT]],
        'find' => ['static' => true, 'returnType' => self::RETURN_TYPE_ITEM, 'params' => ['criteria' => self::PARAM_FIND_CRITERIA]],
        'findOrCreate' => ['static' => true, 'returnType' => self::RETURN_TYPE_ITEM, 'params' => ['attributes' => self::PARAM_ATTRIBUTES]],
        'first' => ['static' => true, 'returnType' => self::RETURN_TYPE_ITEM, 'params' => ['sortedField' => self::PARAM_SORTED_FIELD]],
        'last' => ['static' => true, 'returnType' => self::RETURN_TYPE_ITEM, 'params' => ['sortedField' => self::PARAM_SORTED_FIELD]],
        'random' => ['static' => true, 'returnType' => self::RETURN_TYPE_ITEM, 'params' => ['attributes' => self::PARAM_ATTRIBUTES_DEFAULT]],
        'randomOrCreate' => ['static' => true, 'returnType' => self::RETURN_TYPE_ITEM, 'params' => ['attributes' => self::PARAM_ATTRIBUTES_DEFAULT]],

        'all' => ['static' => true, 'returnType' => self::RETURN_TYPE_LIST, 'params' => []],
        'createMany' => ['static' => true, 'returnType' => self::RETURN_TYPE_LIST, 'params' => ['number' => self::PARAM_INT, 'attributes' => self::PARAM_ATTRIBUTES_CALLABLE]],
        'createSequence' => ['static' => true, 'returnType' => self::RETURN_TYPE_LIST, 'params' => ['sequence' => self::PARAM_SEQUENCE]],
        'findBy' => ['static' => true, 'returnType' => self::RETURN_TYPE_LIST, 'params' => ['attributes' => self::PARAM_ATTRIBUTES]],
        'randomRange' => [
            'static' => true,
            'returnType' => self::RETURN_TYPE_LIST,
            'params' => ['min' => self::PARAM_INT, 'max' => self::PARAM_INT, 'attributes' => self::PARAM_ATTRIBUTES_DEFAULT],
        ],
        'randomSet' => ['static' => true, 'returnType' => self::RETURN_TYPE_LIST, 'params' => ['number' => self::PARAM_INT, 'attributes' => self::PARAM_ATTRIBUTES_DEFAULT]],

        'many' => ['static' => false, 'returnType' => self::RETURN_TYPE_FACTORY_COLLECTION, 'params' => ['min' => self::PARAM_INT, 'max' => self::PARAM_INT_NULLABLE]],
        'sequence' => ['static' => false, 'returnType' => self::RETURN_TYPE_FACTORY_COLLECTION, 'params' => ['sequence' => self::PARAM_SEQUENCE]],
    ];

    private const RETURN_TYPE_ITEM = 'item';
    private const RETURN_TYPE_LIST = 'list';
    private const RETURN_TYPE_FACTORY_COLLECTION = 'factory_collection';

    private const PARAM_ATTRIBUTES = 'attributes';
    private const PARAM_ATTRIBUTES_DEFAULT = 'attributes_default';
    private const PARAM_ATTRIBUTES_CALLABLE = 'attributes_callable';
    private const PARAM_FIND_CRITERIA = 'find_criteria';
    private const PARAM_SORTED_FIELD = 'sorted_field';
    private const PARAM_INT = 'int';
    private const PARAM_INT_NULLABLE = 'int_nullable';
    private const PARAM_SEQUENCE = 'sequence';

        public static function allMethodNames(): array
    {
        return [
            ...array_keys(self::METHODS),
            'repository'
        ];
    }

    /**
     * @param class-string $targetClassName
     */
    public static function methodTag(string $methodName, string $targetClassName, bool $preciseType): PhpDocTagNode
    {
        return new PhpDocTagNode(
            $preciseType ? '@phpstan-method' : '@method',
            new MethodTagValueNode(
                isStatic: self::METHODS[$methodName]['static'],
                returnType: MethodTagFactory::returnType($targetClassName, self::METHODS[$methodName]['returnType'], $preciseType),
                methodName: $methodName,
                parameters: array_map(
                    static fn(string $name, string $paramType) => self::parameter($name, $paramType),
                    array_keys(self::METHODS[$methodName]['params']),
                    self::METHODS[$methodName]['params']
                ),
                description: ''
            )
        );
    }

    /**
     * @param class-string $targetClassName
     * @param class-string<ObjectRepository> $repositoryClassName
     */
    public static function repositoryMethodTag(string $targetClassName, string $repositoryClassName): PhpDocTagNode
    {
        return new PhpDocTagNode(
            '@method',
            new MethodTagValueNode(
                isStatic: true,
                returnType: new GenericTypeNode(
                    new FullyQualifiedIdentifierTypeNode(ProxyRepositoryDecorator::class),
                    [
                        new FullyQualifiedIdentifierTypeNode($targetClassName),
                        new FullyQualifiedIdentifierTypeNode($repositoryClassName),
                    ]
                ),
                methodName: 'repository',
                parameters: [],
                description: ''
            )
        );
    }

    /**
     * @param class-string $targetClassName
     * @param self::RETURN_TYPE_* $returnType
     */
    private static function returnType(string $targetClassName, string $returnType, bool $preciseType): TypeNode
    {
        return match ($returnType) {
            self::RETURN_TYPE_ITEM => self::proxyfiedType($targetClassName, $preciseType),

            self::RETURN_TYPE_LIST => $preciseType
                ? new GenericTypeNode(
                    new IdentifierTypeNode('list'),
                    [self::proxyfiedType($targetClassName, true)]
                )
                : new BracketsAwareUnionTypeNode(
                [
                    new SpacingAwareArrayTypeNode(new FullyQualifiedIdentifierTypeNode($targetClassName)),
                        new SpacingAwareArrayTypeNode(new FullyQualifiedIdentifierTypeNode(Proxy::class)),
                ]
            ),

            self::RETURN_TYPE_FACTORY_COLLECTION => new GenericTypeNode(
                new FullyQualifiedIdentifierTypeNode(FactoryCollection::class),
                [self::proxyfiedType($targetClassName, $preciseType)]
            ),
        };
    }

    private static function proxyfiedType(string $targetClassName, bool $preciseType): TypeNode
    {
        return $preciseType
            ? new BracketsAwareIntersectionTypeNode(
                [
                    new FullyQualifiedIdentifierTypeNode($targetClassName),
                    new GenericTypeNode(
                        new FullyQualifiedIdentifierTypeNode(Proxy::class),
                        [new FullyQualifiedIdentifierTypeNode($targetClassName)]
                    ),
                ]
            )
            : new BracketsAwareUnionTypeNode(
                [
                    new FullyQualifiedIdentifierTypeNode($targetClassName),
                    new FullyQualifiedIdentifierTypeNode(Proxy::class),
                ]
            );
    }

    /**
     * @param self::PARAM_* $paramType
     */
    private static function parameter(string $name, string $paramType): MethodTagValueParameterNode
    {
        $createParameter = fn(TypeNode $type, ConstExprNode|null $default = null) => new MethodTagValueParameterNode(
            type: $type,
            isReference: false,
            isVariadic: false,
            parameterName: "\${$name}",
            defaultValue: $default
        );

        return match ($paramType) {
            self::PARAM_ATTRIBUTES => $createParameter(
                type: new IdentifierTypeNode('array'),
            ),
            self::PARAM_ATTRIBUTES_DEFAULT => $createParameter(
                type: new IdentifierTypeNode('array'),
                default: new ConstExprArrayNode([])
            ),
            self::PARAM_ATTRIBUTES_CALLABLE => $createParameter(
                type: new BracketsAwareUnionTypeNode(
                    [
                        new IdentifierTypeNode('array'),
                        new IdentifierTypeNode('callable'),
                    ]
                ),
                default: new ConstExprArrayNode([])
            ),
            self::PARAM_FIND_CRITERIA => $createParameter(
                type: new BracketsAwareUnionTypeNode(
                    [
                        new IdentifierTypeNode('object'),
                        new IdentifierTypeNode('array'),
                        new IdentifierTypeNode('mixed'),
                    ]
                ),
            ),
            self::PARAM_SEQUENCE => $createParameter(
                type: new BracketsAwareUnionTypeNode(
                    [
                        new IdentifierTypeNode('iterable'),
                        new IdentifierTypeNode('callable'),
                    ]
                ),
            ),
            self::PARAM_SORTED_FIELD => $createParameter(
                type: new IdentifierTypeNode('string'),
                default: new ConstExprStringNode("'id'")
            ),
            self::PARAM_INT => $createParameter(
                type: new IdentifierTypeNode('int'),
            ),
            self::PARAM_INT_NULLABLE => $createParameter(
                type: new BracketsAwareUnionTypeNode(
                    [
                        new IdentifierTypeNode('int'),
                        new IdentifierTypeNode('null'),
                    ]
                ),
            ),
        };
    }
}
