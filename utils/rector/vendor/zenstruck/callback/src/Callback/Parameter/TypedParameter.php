<?php

namespace Zenstruck\Callback\Parameter;

use Zenstruck\Callback\Argument;
use Zenstruck\Callback\Exception\UnresolveableArgument;
use Zenstruck\Callback\Parameter;
use Zenstruck\Callback\ValueFactory;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class TypedParameter extends Parameter
{
    /** @var string */
    private $type;

    /** @var mixed */
    private $value;

    /** @var int */
    private $options;

    /**
     * @param string             $type    The supported type (native or class)
     * @param mixed|ValueFactory $value
     * @param int                $options {@see Argument::supports()}
     */
    public function __construct(string $type, $value, int $options = Argument::EXACT|Argument::COVARIANCE|Argument::CONTRAVARIANCE|Argument::VERY_STRICT)
    {
        $this->type = $type;
        $this->value = $value;
        $this->options = $options;
    }

    public function type(): string
    {
        return $this->type;
    }

    protected function valueFor(Argument $argument)
    {
        if (!$argument->hasType()) {
            throw new UnresolveableArgument('Argument has no type.');
        }

        if ($argument->supports($this->type, $this->options)) {
            return $this->value;
        }

        throw new UnresolveableArgument('Unable to resolve.');
    }
}
