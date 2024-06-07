<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Maker\Factory;

use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Component\Console\Input\InputInterface;

/**
 * @internal
 */
final class MakeFactoryQuery
{
    private function __construct(
        private string $namespace,
        private bool $test,
        private bool $persisted,
        private bool $allFields,
        private bool $withPhpDoc,
        private string $class,
        private bool $generateAllFactories,
        private Generator $generator,
    ) {
    }

    public static function fromInput(InputInterface $input, string $class, bool $generateAllFactories, Generator $generator, string $defaultNamespace): self
    {
        return new self(
            namespace: $defaultNamespace,
            test: (bool) $input->getOption('test'),
            persisted: !$input->getOption('no-persistence'),
            allFields: (bool) $input->getOption('all-fields'),
            withPhpDoc: (bool) $input->getOption('with-phpdoc'),
            class: $class,
            generateAllFactories: $generateAllFactories,
            generator: $generator,
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

    public function addPhpDoc(): bool
    {
        return $this->withPhpDoc;
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
