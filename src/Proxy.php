<?php

namespace Zenstruck\Foundry;

use Doctrine\Persistence\ObjectManager;
use Doctrine\Persistence\ObjectRepository;
use PHPUnit\Framework\Assert;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class Proxy
{
    private static bool $autoRefreshByDefault = true;
    private static ?Instantiator $instantiator = null;

    private object $object;
    private string $class;
    private ?bool $autoRefresh = null;

    public function __construct(object $object)
    {
        $this->object = $object;
        $this->class = \get_class($object);
    }

    public function __call(string $method, array $arguments)
    {
        return $this->object()->{$method}(...$arguments);
    }

    public function __get(string $name)
    {
        return $this->object()->{$name};
    }

    public function __set(string $name, $value): void
    {
        $this->object()->{$name} = $value;
    }

    public function __unset(string $name): void
    {
        unset($this->object()->{$name});
    }

    public function __isset(string $name): bool
    {
        return isset($this->object()->{$name});
    }

    public function __toString(): string
    {
        if (!\method_exists($this->object, '__toString')) {
            throw new \RuntimeException(\sprintf('Proxied object "%s" cannot be converted to a string.', $this->class));
        }

        return $this->object()->__toString();
    }

    public function object(): object
    {
        if ($this->autoRefresh ?? self::$autoRefreshByDefault) {
            $this->refresh();
        }

        return $this->object;
    }

    public function save(): self
    {
        $this->objectManager()->persist($this->object);
        $this->objectManager()->flush();

        return $this;
    }

    public function remove(): self
    {
        $this->objectManager()->remove($this->object);
        $this->objectManager()->flush();

        return $this;
    }

    public function refresh(): self
    {
        if ($this->objectManager()->contains($this->object)) {
            $this->objectManager()->refresh($this->object);

            return $this;
        }

        if (!$object = $this->fetchObject()) {
            throw new \RuntimeException('The object no longer exists.');
        }

        $this->object = $object;

        return $this;
    }

    /**
     * @param mixed $value
     */
    public function forceSet(string $property, $value): self
    {
        return $this->forceSetAll([$property => $value]);
    }

    public function forceSetAll(array $properties): self
    {
        $object = $this->object();

        foreach ($properties as $property => $value) {
            self::instantiator()->forceSet($object, $property, $value);
        }

        return $this;
    }

    /**
     * @return mixed
     */
    public function forceGet(string $property)
    {
        return self::instantiator()->forceGet($this->object(), $property);
    }

    /**
     * @return RepositoryProxy|ObjectRepository
     */
    public function repository(bool $proxy = true): ObjectRepository
    {
        return PersistenceManager::repositoryFor($this->class, $proxy);
    }

    public function withAutoRefresh(): self
    {
        $this->autoRefresh = true;

        return $this;
    }

    public function withoutAutoRefresh(): self
    {
        $this->autoRefresh = false;

        return $this;
    }

    public function assertPersisted(): self
    {
        // todo improve message
        Assert::assertNotNull($this->fetchObject(), 'The object is not persisted.');

        return $this;
    }

    public function assertNotPersisted(): self
    {
        // todo improve message
        Assert::assertNull($this->fetchObject(), 'The object is persisted but it should not be.');

        return $this;
    }

    public static function autoRefreshByDefault(bool $value): void
    {
        self::$autoRefreshByDefault = $value;
    }

    /**
     * Todo - move to RepositoryProxy?
     */
    private function fetchObject(): ?object
    {
        $id = $this->objectManager()->getClassMetadata($this->class)->getIdentifierValues($this->object);

        return empty($id) ? null : $this->objectManager()->find($this->class, $id);
    }

    private function objectManager(): ObjectManager
    {
        return PersistenceManager::objectManagerFor($this->class);
    }

    private static function instantiator(): Instantiator
    {
        return self::$instantiator ?: self::$instantiator = Instantiator::default()->strict();
    }
}
