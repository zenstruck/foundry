<?php declare(strict_types = 1);

namespace PHPStan\Reflection\Doctrine;

use PHPStan\Reflection\ParameterReflection;
use PHPStan\Reflection\PassedByReference;
use PHPStan\Type\Type;

class DummyParameter implements ParameterReflection
{

	/** @var string */
	private $name;

	/** @var Type */
	private $type;

	/** @var bool */
	private $optional;

	/** @var PassedByReference */
	private $passedByReference;

	/** @var bool */
	private $variadic;

	/** @var Type|null */
	private $defaultValue;

	public function __construct(string $name, Type $type, bool $optional, ?PassedByReference $passedByReference, bool $variadic, ?Type $defaultValue)
	{
		$this->name = $name;
		$this->type = $type;
		$this->optional = $optional;
		$this->passedByReference = $passedByReference ?? PassedByReference::createNo();
		$this->variadic = $variadic;
		$this->defaultValue = $defaultValue;
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function isOptional(): bool
	{
		return $this->optional;
	}

	public function getType(): Type
	{
		return $this->type;
	}

	public function passedByReference(): PassedByReference
	{
		return $this->passedByReference;
	}

	public function isVariadic(): bool
	{
		return $this->variadic;
	}

	public function getDefaultValue(): ?Type
	{
		return $this->defaultValue;
	}

}
