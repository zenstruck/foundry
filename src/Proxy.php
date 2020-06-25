<?php

namespace Zenstruck\Foundry;

use Doctrine\Persistence\ObjectManager;
use PHPUnit\Framework\Assert;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class Proxy
{
    /** @var object */
    private $object;

    /** @var string */
    private $class;

    /** @var bool */
    private $autoRefresh = false;

    /** @var bool */
    private $persisted = false;

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
            if (\PHP_VERSION_ID < 70400) {
                return '(no __toString)';
            }

            throw new \RuntimeException(\sprintf('Proxied object "%s" cannot be converted to a string.', $this->class));
        }

        return $this->object()->__toString();
    }

    public static function persisted(object $object): self
    {
        $proxy = new self($object);
        $proxy->persisted = $proxy->autoRefresh = true;

        return $proxy;
    }

    public function isPersisted(): bool
    {
        return $this->persisted;
    }

    public function object(): object
    {
        if ($this->autoRefresh && $this->persisted) {
            $this->refresh();
        }

        return $this->object;
    }

    public function save(): self
    {
        $this->objectManager()->persist($this->object);
        $this->objectManager()->flush();
        $this->autoRefresh = $this->persisted = true;

        return $this;
    }

    public function remove(): self
    {
        $this->objectManager()->remove($this->object);
        $this->objectManager()->flush();
        $this->autoRefresh = $this->persisted = false;

        return $this;
    }

    public function refresh(): self
    {
        if (!$this->persisted) {
            throw new \RuntimeException(\sprintf('Cannot refresh unpersisted object (%s).', $this->class));
        }

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
            Instantiator::forceSet($object, $property, $value);
        }

        return $this;
    }

    /**
     * @return mixed
     */
    public function forceGet(string $property)
    {
        return Instantiator::forceGet($this->object(), $property);
    }

    public function repository(): RepositoryProxy
    {
        return Factory::configuration()->repositoryFor($this->class);
    }

    public function enableAutoRefresh(): self
    {
        if (!$this->persisted) {
            throw new \RuntimeException(\sprintf('Cannot enable auto-refresh on unpersisted object (%s).', $this->class));
        }

        $this->autoRefresh = true;

        return $this;
    }

    public function disableAutoRefresh(): self
    {
        $this->autoRefresh = false;

        return $this;
    }

    public function withoutAutoRefresh(callable $callback): self
    {
        $this->disableAutoRefresh();

        $this->executeCallback($callback);

        return $this->isPersisted() ? $this->enableAutoRefresh() : $this;
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

    /**
     * @internal
     */
    public function executeCallback(callable $callback, ...$arguments): void
    {
        $object = $this;
        $parameters = (new \ReflectionFunction($callback))->getParameters();

        if (isset($parameters[0]) && $parameters[0]->getType() && $this->class === $parameters[0]->getType()->getName()) {
            $object = $object->object();
        }

        $callback($object, ...$arguments);
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
        return Factory::configuration()->objectManagerFor($this->class);
    }
}
