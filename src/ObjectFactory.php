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

use Symfony\Component\Validator\Constraints\GroupSequence;
use Zenstruck\Foundry\Object\Event\AfterInstantiate;
use Zenstruck\Foundry\Object\Event\BeforeInstantiate;
use Zenstruck\Foundry\Object\Instantiator;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @template T of object
 * @extends Factory<T>
 *
 * @phpstan-type InstantiatorCallable = Instantiator|callable(Parameters,class-string<T>):T
 * @phpstan-import-type Parameters from Factory
 */
abstract class ObjectFactory extends Factory
{
    /** @phpstan-var list<callable(Parameters, class-string<T>, static):Parameters> */
    private array $beforeInstantiate = [];

    /** @phpstan-var list<callable(T, Parameters, static):void> */
    private array $afterInstantiate = [];

    /** @phpstan-var InstantiatorCallable|null */
    private $instantiator;

    /** @phpstan-var array<class-string, object> */
    private array $reusedObjects = [];

    private bool $validationEnabled;

    /** @var string|GroupSequence|list<string>|null */
    private string|GroupSequence|array|null $validationGroups = [];

    // keep an empty constructor for BC
    public function __construct()
    {
        parent::__construct();

        $this->validationEnabled = Configuration::isBooted() && Configuration::instance()->validationEnabled;
    }

    /**
     * @return class-string<T>
     */
    abstract public static function class(): string;

    /**
     * @return T
     */
    public function create(callable|array $attributes = []): object
    {
        $parameters = $this->normalizeAttributes($attributes);

        foreach ($this->beforeInstantiate as $hook) {
            $parameters = $hook($parameters, static::class(), $this);

            if (!\is_array($parameters)) {
                throw new \LogicException('Before Instantiate hook callback must return a parameter array.');
            }
        }

        $parameters = $this->normalizeParameters($parameters);
        $instantiator = $this->instantiator ?? Configuration::instance()->instantiator;
        /** @var T $object */
        $object = $instantiator($parameters, static::class());

        foreach ($this->afterInstantiate as $hook) {
            $hook($object, $parameters, $this);
        }

        return $object;
    }

    /**
     * @phpstan-param InstantiatorCallable $instantiator
     *
     * @psalm-return static<T>
     * @phpstan-return static
     */
    final public function instantiateWith(callable $instantiator): static
    {
        $clone = clone $this;
        $clone->instantiator = $instantiator;

        return $clone;
    }

    /**
     * @phpstan-param callable(Parameters, class-string<T>, static):Parameters $callback
     */
    final public function beforeInstantiate(callable $callback): static
    {
        $clone = clone $this;
        $clone->beforeInstantiate[] = $callback;

        return $clone;
    }

    /**
     * @final
     *
     * @phpstan-param callable(T, Parameters, static):void $callback
     */
    public function afterInstantiate(callable $callback): static
    {
        $clone = clone $this;
        $clone->afterInstantiate[] = $callback;

        return $clone;
    }

    /**
     * @param string|GroupSequence|list<string>|null $groups
     *
     * @psalm-return static<T>
     * @phpstan-return static
     */
    public function withValidation(string|GroupSequence|array|null $groups = null): static
    {
        if (!Configuration::instance()->validationAvailable) {
            throw new \LogicException('Validation is not available. Make sure the "symfony/validator" package is installed and validation enabled.');
        }

        $clone = clone $this;
        $clone->validationEnabled = true;

        if (null !== $groups) {
            $clone->validationGroups = $groups;
        }

        return $clone;
    }

    /**
     * @psalm-return static<T>
     * @phpstan-return static
     */
    public function withoutValidation(): static
    {
        $clone = clone $this;
        $clone->validationEnabled = false;

        return $clone;
    }

    /**
     * @param string|GroupSequence|list<string>|null $groups
     *
     * @psalm-return static<T>
     * @phpstan-return static
     */
    public function withValidationGroups(string|GroupSequence|array|null $groups): static
    {
        if (!Configuration::instance()->validationAvailable) {
            throw new \LogicException('Validation is not available. Make sure the "symfony/validator" package is installed and validation enabled.');
        }

        $clone = clone $this;
        $clone->validationGroups = $groups;

        return $clone;
    }

    /**
     * @internal
     */
    public function validationEnabled(): bool
    {
        return $this->validationEnabled;
    }

    /**
     * @return string|GroupSequence|list<string>|null
     *
     * @internal
     */
    public function getValidationGroups(): string|GroupSequence|array|null
    {
        return $this->validationGroups;
    }

    /**
     * @psalm-return static<T>
     * @phpstan-return static
     */
    final public function reuse(object $object): static
    {
        if (isset($this->reusedObjects[$object::class])) {
            throw new \InvalidArgumentException(\sprintf('An object of class "%s" is already being reused.', $object::class));
        }

        if ($object instanceof Factory) {
            throw new \InvalidArgumentException('Cannot reuse a factory.');
        }

        $clone = clone $this;
        $clone->reusedObjects[$object::class] = $object;

        return $clone;
    }

    protected function normalizeParameter(string $field, mixed $value): mixed
    {
        if ($value instanceof self) {
            // propagate "reused" objects
            foreach ($this->reusedObjects as $item) {
                // "reused" item in the target factory have priority, if they are of the same type
                if (!isset($value->reusedObjects[$item::class])) {
                    $value = $value->reuse($item);
                }
            }
        }

        return parent::normalizeParameter($field, $value);
    }

    /**
     * @internal
     * @phpstan-return Parameters
     */
    final protected function reusedAttributes(): array
    {
        $attributes = [];

        $propertyInfo = Configuration::instance()->propertyInfo;

        $properties = $propertyInfo->getProperties(static::class());
        foreach ($properties ?? [] as $property) {
            $types = $propertyInfo->getTypes(static::class(), $property);

            foreach ($types ?? [] as $type) {
                if (null === $type->getClassName()) {
                    continue;
                }

                if (isset($this->reusedObjects[$type->getClassName()])) {
                    $attributes[$property] = $this->reusedObjects[$type->getClassName()];

                    break;
                }
            }
        }

        return $attributes;
    }

    /**
     * @internal
     */
    protected function initializeInternal(): static
    {
        if (!Configuration::isBooted() || !Configuration::instance()->hasEventDispatcher()) {
            return $this;
        }

        return $this->beforeInstantiate(
            static function(array $parameters, string $objectClass, self $usedFactory): array {
                Configuration::instance()->eventDispatcher()->dispatch(
                    $hook = new BeforeInstantiate($parameters, $objectClass, $usedFactory)
                );

                return $hook->parameters;
            }
        )
            ->afterInstantiate(
                static function(object $object, array $parameters, self $usedFactory): void {
                    Configuration::instance()->eventDispatcher()->dispatch(
                        new AfterInstantiate($object, $parameters, $usedFactory)
                    );
                }
            );
    }
}
