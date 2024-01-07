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
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;
use Zenstruck\Foundry\Persistence\Proxy as ProxyBase;
use Zenstruck\Foundry\Persistence\RepositoryDecorator;

/**
 * @deprecated Typehint Zenstruck\Foundry\Persistence\Proxy instead
 *
 * @template TProxiedObject of object
 * @implements ProxyBase<TProxiedObject>
 *
 * @mixin TProxiedObject
 *
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class Proxy implements \Stringable, ProxyBase
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
        private object $object
    ) {
        if ((new \ReflectionClass($object::class))->isFinal()) {
            trigger_deprecation(
                'zenstruck\foundry', '1.37.0',
                'Using a proxy factory with a final class is deprecated and will throw an error in Foundry 2.0. Use "%s" instead (don\'t forget to remember all ->object() calls!), or unfinalize "%s" class.',
                PersistentProxyObjectFactory::class,
                $object::class
            );
        }

        $this->class = $object::class;
        $this->autoRefresh = Factory::configuration()->defaultProxyAutoRefresh();
    }

    public function __call(string $method, array $arguments) // @phpstan-ignore-line
    {
        return $this->_real()->{$method}(...$arguments);
    }

    public function __get(string $name): mixed
    {
        return $this->_real()->{$name};
    }

    public function __set(string $name, mixed $value): void
    {
        $this->_real()->{$name} = $value;
    }

    public function __unset(string $name): void
    {
        unset($this->_real()->{$name});
    }

    public function __isset(string $name): bool
    {
        return isset($this->_real()->{$name});
    }

    public function __toString(): string
    {
        $object = $this->_real();

        if (!\method_exists($object, '__toString')) {
            throw new \RuntimeException(\sprintf('Proxied object "%s" cannot be converted to a string.', $this->class));
        }

        return $object->__toString();
    }

    /**
     * @internal
     *
     * @template TObject of object
     * @phpstan-param TObject $object
     * @phpstan-return ProxyBase<TObject>
     */
    public static function createFromPersisted(object $object): self
    {
        $proxy = new self($object);
        $proxy->persisted = true;

        return $proxy;
    }

    /**
     * @deprecated without replacement
     */
    public function isPersisted(bool $calledInternally = false): bool
    {
        if (!$calledInternally) {
            trigger_deprecation('zenstruck\foundry', '1.37.0', 'Method "%s()" is deprecated and will be removed in 2.0 without replacement.', __METHOD__);
        }

        return $this->persisted;
    }

    /**
     * @return TProxiedObject
     *
     * @deprecated Use method "_real()" instead
     */
    public function object(): object
    {
        trigger_deprecation('zenstruck\foundry', '1.37.0', 'Method "%s()" is deprecated and will be removed in 2.0. Use "%s::_real()" instead.', __METHOD__, self::class);

        return $this->_real();
    }

    public function _real(): object
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

        $this->_refresh();

        return $this->object;
    }

    /**
     * @deprecated Use method "_save()" instead
     */
    public function save(): static
    {
        trigger_deprecation('zenstruck\foundry', '1.37.0', 'Method "%s()" is deprecated and will be removed in 2.0. Use "%s::_save()" instead.', __METHOD__, self::class);

        return $this->_save();
    }

    public function _save(): static
    {
        $this->objectManager()->persist($this->object);

        if (Factory::configuration()->isFlushingEnabled()) {
            $this->objectManager()->flush();
        }

        $this->persisted = true;

        return $this;
    }

    /**
     * @deprecated Use method "_delete()" instead
     */
    public function remove(): static
    {
        trigger_deprecation('zenstruck\foundry', '1.37.0', 'Method "%s()" is deprecated and will be removed in 2.0. Use "%s::_delete()" instead.', __METHOD__, self::class);

        return $this->_delete();
    }

    public function _delete(): static
    {
        $this->objectManager()->remove($this->object);
        $this->objectManager()->flush();
        $this->autoRefresh = false;
        $this->persisted = false;

        return $this;
    }

    /**
     * @deprecated Use method "_refresh()" instead
     */
    public function refresh(): static
    {
        trigger_deprecation('zenstruck\foundry', '1.37.0', 'Method "%s()" is deprecated and will be removed in 2.0. Use "%s::_refresh()" instead.', __METHOD__, self::class);

        return $this->_refresh();
    }

    public function _refresh(): static
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

    /**
     * @deprecated Use method "_set()" instead
     */
    public function forceSet(string $property, mixed $value): static
    {
        trigger_deprecation('zenstruck\foundry', '1.37.0', 'Method "%s()" is deprecated and will be removed in 2.0. Use "%s::_set()" instead.', __METHOD__, self::class);

        return $this->_set($property, $value);
    }

    public function _set(string $property, mixed $value): static
    {
        $object = $this->_real();

        Instantiator::forceSet($object, $property, $value);

        return $this;
    }

    /**
     * @deprecated without replacement
     */
    public function forceSetAll(array $properties): static
    {
        trigger_deprecation('zenstruck\foundry', '1.37.0', 'Method "%s()" is deprecated and will be removed in 2.0 without replacement.', __METHOD__);

        $object = $this->_real();

        foreach ($properties as $property => $value) {
            Instantiator::forceSet($object, $property, $value);
        }

        return $this;
    }

    /**
     * @deprecated Use method "_get()" instead
     */
    public function get(string $property): mixed
    {
        trigger_deprecation('zenstruck\foundry', '1.37.0', 'Method "%s()" is deprecated and will be removed in 2.0. Use "%s::_get()" instead.', __METHOD__, self::class);

        return $this->_get($property);
    }

    public function _get(string $property): mixed
    {
        return Instantiator::forceGet($this->_real(), $property);
    }

    /**
     * @deprecated Use method "_repository()" instead
     */
    public function repository(): RepositoryDecorator
    {
        trigger_deprecation('zenstruck\foundry', '1.37.0', 'Method "%s()" is deprecated and will be removed in 2.0. Use "%s::_repository()" instead.', __METHOD__, self::class);

        return $this->_repository();
    }

    public function _repository(): RepositoryDecorator
    {
        return Factory::configuration()->repositoryFor($this->class);
    }

    /**
     * @deprecated Use method "_enableAutoRefresh()" instead
     */
    public function enableAutoRefresh(): static
    {
        trigger_deprecation('zenstruck\foundry', '1.37.0', 'Method "%s()" is deprecated and will be removed in 2.0. Use "%s::_enableAutoRefresh()" instead.', __METHOD__, self::class);

        return $this->_enableAutoRefresh();
    }

    public function _enableAutoRefresh(): static
    {
        if (!$this->persisted) {
            throw new \RuntimeException(\sprintf('Cannot enable auto-refresh on unpersisted object (%s).', $this->class));
        }

        $this->autoRefresh = true;

        return $this;
    }

    /**
     * @deprecated Use method "_disableAutoRefresh()" instead
     */
    public function disableAutoRefresh(): static
    {
        trigger_deprecation('zenstruck\foundry', '1.37.0', 'Method "%s()" is deprecated and will be removed in 2.0. Use "%s::_disableAutoRefresh()" instead.', __METHOD__, self::class);

        return $this->_disableAutoRefresh();
    }

    public function _disableAutoRefresh(): static
    {
        $this->autoRefresh = false;

        return $this;
    }

    /**
     * @param callable $callback (object|Proxy $object): void
     *
     * @deprecated Use method "_withoutAutoRefresh()" instead
     */
    public function withoutAutoRefresh(callable $callback): static
    {
        trigger_deprecation('zenstruck\foundry', '1.37.0', 'Method "%s()" is deprecated and will be removed in 2.0. Use "%s::_withoutAutoRefresh()" instead.', __METHOD__, self::class);

        return $this->_withoutAutoRefresh($callback);
    }

    public function _withoutAutoRefresh(callable $callback): static
    {
        $original = $this->autoRefresh;
        $this->autoRefresh = false;

        $this->executeCallback($callback);

        $this->autoRefresh = $original; // set to original value (even if it was false)

        return $this;
    }

    /**
     * @deprecated without replacement
     */
    public function assertPersisted(string $message = '{entity} is not persisted.'): self
    {
        trigger_deprecation('zenstruck\foundry', '1.37.0', 'Method "%s()" is deprecated and will be removed in 2.0 without replacement.', __METHOD__);

        Assert::that($this->fetchObject())->isNotEmpty($message, ['entity' => $this->class]);

        return $this;
    }

    /**
     * @deprecated without replacement
     */
    public function assertNotPersisted(string $message = '{entity} is persisted but it should not be.'): self
    {
        trigger_deprecation('zenstruck\foundry', '1.37.0', 'Method "%s()" is deprecated and will be removed in 2.0 without replacement.', __METHOD__);

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
                Parameter::typed(ProxyBase::class, $this),
                Parameter::typed($this->class, Parameter::factory(fn(): object => $this->_real()))
            )->optional(),
            ...$arguments
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
