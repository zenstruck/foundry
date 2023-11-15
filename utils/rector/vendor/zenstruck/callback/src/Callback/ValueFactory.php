<?php

namespace Zenstruck\Callback;

use Zenstruck\Callback;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ValueFactory
{
    /** @var callable */
    private $factory;

    /**
     * @param callable<string|array|Argument|null> $factory
     */
    public function __construct(callable $factory)
    {
        $this->factory = $factory;
    }

    public function __invoke(Argument $argument)
    {
        $stringTypeFactory = Parameter::factory(function() use ($argument) {
            if ($argument->isUnionType()) {
                throw new \LogicException(\sprintf('ValueFactory does not support union types. Inject "%s" instead.', Argument::class));
            }

            return $argument->type();
        });

        return Callback::createFor($this->factory)
            ->invoke(Parameter::union(
                Parameter::typed(Argument::class, $argument),
                Parameter::typed('array', $argument->types()),
                Parameter::typed('string', $stringTypeFactory),
                Parameter::untyped($stringTypeFactory)
            )->optional())
        ;
    }
}
