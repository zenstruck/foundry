<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Tests\Fixture\DoctrineCascadeRelationship;

use PHPUnit\Metadata\DataProvider;
use PHPUnit\Metadata\Metadata;
use PHPUnit\Metadata\MetadataCollection;
use PHPUnit\Metadata\Parser\Parser;

/**
 * Turns #[UsingRelationships] into a data provider: PHPUnit then runs the test once per cascade combination,
 * crossed with the data sets of the test's own data providers.
 *
 * Relies on PHPUnit internals: {@see PhpUnitTestExtension::bootstrap()} for the warning.
 *
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 */
final class UsingRelationshipsParser implements Parser
{
    public function __construct(private readonly Parser $decorated)
    {
    }

    public function forClass(string $className): MetadataCollection
    {
        return $this->decorated->forClass($className);
    }

    public function forMethod(string $className, string $methodName): MetadataCollection
    {
        $metadata = $this->decorated->forMethod($className, $methodName);

        if (!(new \ReflectionMethod($className, $methodName))->getAttributes(UsingRelationships::class)) {
            return $metadata;
        }

        if (!\method_exists($className, 'cascadeRelationshipsCombinations')) {
            throw new \LogicException("Test \"{$className}::{$methodName}()\" should use trait ChangesEntityRelationshipCascadePersist.");
        }

        /** @var list<DataProvider> $dataProviders */
        $dataProviders = $metadata->isDataProvider()->asArray();

        return MetadataCollection::fromArray([
            ...\array_values(\array_filter($metadata->asArray(), static fn(Metadata $m): bool => !$m->isDataProvider())),
            Metadata::dataProviderClosure(
                static fn(): iterable => $className::cascadeRelationshipsCombinations($methodName, \array_merge(...\array_map(
                    static fn(DataProvider $dataProvider): array => [...$dataProvider->className()::{$dataProvider->methodName()}()],
                    $dataProviders,
                ))),
                [] !== $dataProviders, // without data provider, the test method does not accept the combination it receives
            ),
        ]);
    }

    public function forClassAndMethod(string $className, string $methodName): MetadataCollection
    {
        return $this->forClass($className)->mergeWith($this->forMethod($className, $methodName));
    }
}
