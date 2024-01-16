<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectManager;
use Zenstruck\Assert;
use Zenstruck\Callback;
use Zenstruck\Callback\Parameter;

/**
 * @template TProxiedObject of object
 * @mixin TProxiedObject
 *
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class Proxy implements \Stringable
{
    /**
     * @phpstan-var class-string<TProxiedObject>
     */
    private string $class;

    private bool $autoRefresh;

    private bool $persisted = false;

    /**
     * @internal
     *
     * @phpstan-param TProxiedObject $object
     */
    public function __construct(
        /** @param TProxiedObject $object */
        private object $object,
    ) {
        $this->class = $object::class;
        $this->autoRefresh = Factory::configuration()->defaultProxyAutoRefresh();
    }

    public function __call(string $method, array $arguments) // @phpstan-ignore-line
    {
        return $this->object()->{$method}(...$arguments);
    }

    public function __get(string $name): mixed
    {
        return $this->object()->{$name};
    }

    public function __set(string $name, mixed $value): void
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
        $object = $this->object();

        if (!\method_exists($object, '__toString')) {
            if (\PHP_VERSION_ID < 70400) {
                return '(no __toString)';
            }

            throw new \RuntimeException(\sprintf('Proxied object "%s" cannot be converted to a string.', $this->class));
        }

        return $object->__toString();
    }

    /**
     * @internal
     *
     * @template TObject of object
     * @phpstan-param TObject $object
     * @phpstan-return Proxy<TObject>
     */
    public static function createFromPersisted(object $object): self
    {
        $proxy = new self($object);
        $proxy->persisted = true;

        return $proxy;
    }

    public function isPersisted(): bool
    {
        return $this->persisted;
    }

    /**
     * @return TProxiedObject
     */
    public function object(): object
    {
        if (!$this->autoRefresh || !$this->persisted || !Factory::configuration()->isFlushingEnabled() || !Factory::configuration()->isPersistEnabled()) {
            return $this->object;
        }

        $om = $this->objectManager();

        // only check for changes if the object is managed in the current om
        if (($om instanceof EntityManagerInterface || $om instanceof DocumentManager) && $om->contains($this->object)) {
            // cannot use UOW::recomputeSingleEntityChangeSet() here as it wrongly computes embedded objects as changed
            $om->getUnitOfWork()->computeChangeSet($om->getClassMetadata($this->class), $this->object);

            if (
                ($om instanceof EntityManagerInterface && !empty($om->getUnitOfWork()->getEntityChangeSet($this->object)))
                || ($om instanceof DocumentManager && !empty($om->getUnitOfWork()->getDocumentChangeSet($this->object)))) {
                throw new \RuntimeException(\sprintf('Cannot auto refresh "%s" as there are unsaved changes. Be sure to call ->save() or disable auto refreshing (see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#auto-refresh for details).', $this->class));
            }
        }

        $this->refresh();

        return $this->object;
    }

    /**
     * @phpstan-return static
     */
    public function save(): self
    {
        $this->objectManager()->persist($this->object);

        if (Factory::configuration()->isFlushingEnabled()) {
            $this->objectManager()->flush();
        }

        $this->persisted = true;

        return $this;
    }

    public function remove(): self
    {
        $this->objectManager()->remove($this->object);
        $this->objectManager()->flush();
        $this->autoRefresh = false;
        $this->persisted = false;

        return $this;
    }

    public function refresh(): self
    {
        if (!Factory::configuration()->isPersistEnabled()) {
            return $this;
        }

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

    public function forceSet(string $property, mixed $value): self
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

    /**
     * Ensures "autoRefresh" is disabled when executing $callback. Re-enables
     * "autoRefresh" after executing callback if it was enabled.
     *
     * @param callable $callback (object|Proxy $object): void
     *
     * @phpstan-return static
     */
    public function withoutAutoRefresh(callable $callback): self
    {
        $original = $this->autoRefresh;
        $this->autoRefresh = false;

        $this->executeCallback($callback);

        $this->autoRefresh = $original; // set to original value (even if it was false)

        return $this;
    }

    public function assertPersisted(string $message = '{entity} is not persisted.'): self
    {
        Assert::that($this->fetchObject())->isNotEmpty($message, ['entity' => $this->class]);

        return $this;
    }

    public function assertNotPersisted(string $message = '{entity} is persisted but it should not be.'): self
    {
        Assert::that($this->fetchObject())->isEmpty($message, ['entity' => $this->class]);

        return $this;
    }

    /**
     * @internal
     */
    public function executeCallback(callable $callback, mixed ...$arguments): void
    {
        Callback::createFor($callback)->invoke(
            Parameter::union(
                Parameter::untyped($this),
                Parameter::typed(self::class, $this),
                Parameter::typed($this->class, Parameter::factory(fn(): object => $this->object())),
            )->optional(),
            ...$arguments,
        );
    }

    /**
     * @phpstan-return TProxiedObject|null
     */
    private function fetchObject(): ?object
    {
        $objectManager = $this->objectManager();

        if ($objectManager instanceof DocumentManager) {
            $classMetadata = $objectManager->getClassMetadata($this->class);
            if (!$classMetadata->isEmbeddedDocument) {
                $id = $classMetadata->getIdentifierValue($this->object);
            }
        } else {
            $id = $objectManager->getClassMetadata($this->class)->getIdentifierValues($this->object);
        }

        return empty($id) ? null : $objectManager->find($this->class, $id);
    }

    private function objectManager(): ObjectManager
    {
        if (!Factory::configuration()->isPersistEnabled()) {
            throw new \RuntimeException('Should not get doctrine\'s object manager when persist is disabled.');
        }

        return Factory::configuration()->objectManagerFor($this->class);
    }
}
