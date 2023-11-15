<?php

namespace Zenstruck\Callback;

use Zenstruck\Callback\Exception\UnresolveableArgument;
use Zenstruck\Callback\Parameter\TypedParameter;
use Zenstruck\Callback\Parameter\UnionParameter;
use Zenstruck\Callback\Parameter\UntypedParameter;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
abstract class Parameter
{
    /** @var bool */
    private $optional = false;

    /**
     * @see UnionParameter::__construct()
     */
    final public static function union(self ...$parameters): self
    {
        return new UnionParameter(...$parameters);
    }

    /**
     * @see TypedParameter::__construct()
     */
    final public static function typed(string $type, $value, int $options = Argument::EXACT|Argument::COVARIANCE|Argument::CONTRAVARIANCE|Argument::VERY_STRICT): self
    {
        return new TypedParameter($type, $value, $options);
    }

    /**
     * @see UntypedParameter::__construct()
     */
    final public static function untyped($value): self
    {
        return new UntypedParameter($value);
    }

    /**
     * @see ValueFactory::__construct()
     */
    final public static function factory(callable $factory): ValueFactory
    {
        return new ValueFactory($factory);
    }

    final public function optional(): self
    {
        $this->optional = true;

        return $this;
    }

    /**
     * @internal
     *
     * @return mixed
     *
     * @throws UnresolveableArgument
     */
    final public function resolve(Argument $argument)
    {
        try {
            $value = $this->valueFor($argument);
        } catch (UnresolveableArgument $e) {
            if ($argument->isOptional()) {
                return $argument->defaultValue();
            }

            throw $e;
        }

        if ($value instanceof ValueFactory) {
            $value = $value($argument);
        }

        if (!$argument->allows($value)) {
            throw new UnresolveableArgument(\sprintf('Unable to resolve argument. Expected "%s", got "%s".', $argument->type(), get_debug_type($value)));
        }

        return $value;
    }

    /**
     * @internal
     */
    final public function isOptional(): bool
    {
        return $this->optional;
    }

    abstract public function type(): string;

    abstract protected function valueFor(Argument $argument);
}
