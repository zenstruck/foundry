<?php

namespace Zenstruck\Foundry\Bundle\Maker\Factory;

use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Component\Console\Input\InputInterface;

/**
 * @internal
 */
final class MakeFactoryQuery
{
    private function __construct(private string $namespace, private bool $test, private bool $persisted, private bool $allFields, private string $class, private bool $generateAllFactories, private Generator $generator)
    {
    }

    public static function fromInput(InputInterface $input, string $class, bool $generateAllFactories, Generator $generator): self
    {
        return new self(
            namespace: $input->getOption('namespace'),
            test: (bool) $input->getOption('test'),
            persisted: !$input->getOption('no-persistence'),
            allFields: (bool) $input->getOption('all-fields'),
            class: $class,
            generateAllFactories: $generateAllFactories,
            generator: $generator
        );
    }

    public function getNamespace(): string
    {
        return $this->namespace;
    }

    public function isTest(): bool
    {
        return $this->test;
    }

    public function isPersisted(): bool
    {
        return $this->persisted;
    }

    public function isAllFields(): bool
    {
        return $this->allFields;
    }

    public function getClass(): string
    {
        return $this->class;
    }

    public function generateAllFactories(): bool
    {
        return $this->generateAllFactories;
    }

    public function getGenerator(): Generator
    {
        return $this->generator;
    }

    public function withClass(string $class): self
    {
        $clone = clone $this;
        $clone->class = $class;

        return $clone;
    }
}
