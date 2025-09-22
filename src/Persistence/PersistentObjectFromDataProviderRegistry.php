<?php

namespace Zenstruck\Foundry\Persistence;

/**
 * If a persistent object has been created in a data provider, we need to initialize the proxy object,
 * which will trigger the object to be persisted.
 *
 * Otherwise, such test would not pass:
 * ```php
 * #[DataProvider('provide')]
 * public function testSomething(MyEntity $entity): void
 * {
 *     MyEntityFactory::assert()->count(1);
 * }
 *
 * public static function provide(): iterable
 * {
 *     yield [MyEntityFactory::createOne()];
 * }
 * ```
 *
 * Sadly, this cannot be done directly a subscriber, since PHPUnit does not give access to the actual tests instances.
 *
 * This class is highly hacky!
 * We collect all the "datasets" and we trigger the persistence for each one before the test is executed.
 * This means that de data providers are called twice.
 * To prevent the persisted object from being different from the one returned by the data provider, we use a "buffer" so
 * that we can return the same object for each data provider call.
 *
 * @internal
 */
final class PersistentObjectFromDataProviderRegistry
{
    private static ?self $instance = null;

    /** @var array<string, array<array-key, mixed>> */
    private array $datasets = [];

    /** @var list<object> */
    private array $objectsBuffer = [];

    private bool $shouldReturnExistingObject = false;

    public static function instance(): self
    {
        return self::$instance ?? self::$instance = new self();
    }

    /**
     * @param callable():iterable<array-key, mixed> $dataProviderResult
     */
    public function addDataset(string $className, string $methodName, callable $dataProviderResult): void
    {
        $this->shouldReturnExistingObject = false;

        $dataProviderResult = $dataProviderResult();

        if (!is_array($dataProviderResult)) {
            $dataProviderResult = iterator_to_array($dataProviderResult);
        }

        $testCaseContext = $this->testCaseContext($className, $methodName);
        $this->datasets[$testCaseContext] = $dataProviderResult;

        $this->shouldReturnExistingObject = true;
    }

    /**
     * @template T of object
     *
     * @param PersistentObjectFactory<T> $factory
     *
     * @return ($factory is PersistentProxyObjectFactory<T> ? T&Proxy<T> : T)
     */
    public function deferObjectCreation(PersistentObjectFactory $factory): object
    {
        if (!$this->shouldReturnExistingObject) {
            return $this->objectsBuffer[] = ProxyGenerator::wrapFactory($factory);
        }

        return array_shift($this->objectsBuffer); // @phpstan-ignore return.type
    }

    public function triggerPersistenceForDataset(string $className, string $methodName, int|string $dataSetName): void
    {
        $testCaseContext = $this->testCaseContext($className, $methodName);

        if (!isset($this->datasets[$testCaseContext][$dataSetName])) {
            throw new \LogicException("No data found for test case context \"{$testCaseContext}\" with dataset name \"{$dataSetName}\".");
        }

        initialize_proxy_object($this->datasets[$testCaseContext][$dataSetName]);

        unset($this->datasets[$testCaseContext][$dataSetName]);
    }

    private function testCaseContext(string $className, string $methodName): string
    {
        return "{$className}::{$methodName}";
    }
}
