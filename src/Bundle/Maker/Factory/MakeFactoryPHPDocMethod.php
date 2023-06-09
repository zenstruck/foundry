<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Bundle\Maker\Factory;

/**
 * @internal
 */
final class MakeFactoryPHPDocMethod
{
    public function __construct(private string $objectName, private string $prototype, private bool $returnsCollection, private bool $isStatic = true, private string|null $repository = null)
    {
    }

    /** @return non-empty-list<self> */
    public static function createAll(MakeFactoryData $makeFactoryData): array
    {
        $methods = [];

        $methods[] = new self($makeFactoryData->getObjectShortName(), 'create(array|callable $attributes = [])', returnsCollection: false, isStatic: false);
        $methods[] = new self($makeFactoryData->getObjectShortName(), 'createOne(array $attributes = [])', returnsCollection: false);

        $methods[] = new self($makeFactoryData->getObjectShortName(), 'createMany(int $number, array|callable $attributes = [])', returnsCollection: true);
        $methods[] = new self($makeFactoryData->getObjectShortName(), 'createSequence(iterable|callable $sequence)', returnsCollection: true);

        if ($makeFactoryData->isPersisted()) {
            $methods[] = new self($makeFactoryData->getObjectShortName(), 'find(object|array|mixed $criteria)', returnsCollection: false);
            $methods[] = new self($makeFactoryData->getObjectShortName(), 'findOrCreate(array $attributes)', returnsCollection: false);
            $methods[] = new self($makeFactoryData->getObjectShortName(), 'first(string $sortedField = \'id\')', returnsCollection: false);
            $methods[] = new self($makeFactoryData->getObjectShortName(), 'last(string $sortedField = \'id\')', returnsCollection: false);
            $methods[] = new self($makeFactoryData->getObjectShortName(), 'random(array $attributes = [])', returnsCollection: false);
            $methods[] = new self($makeFactoryData->getObjectShortName(), 'randomOrCreate(array $attributes = [])', returnsCollection: false);

            $methods[] = new self($makeFactoryData->getObjectShortName(), 'all()', returnsCollection: true);
            $methods[] = new self($makeFactoryData->getObjectShortName(), 'findBy(array $attributes)', returnsCollection: true);
            $methods[] = new self($makeFactoryData->getObjectShortName(), 'randomRange(int $min, int $max, array $attributes = [])', returnsCollection: true);
            $methods[] = new self($makeFactoryData->getObjectShortName(), 'randomSet(int $number, array $attributes = [])', returnsCollection: true);

            if (null !== $makeFactoryData->getRepositoryShortName()) {
                $methods[] = new self($makeFactoryData->getObjectShortName(), 'repository()', returnsCollection: false, repository: $makeFactoryData->getRepositoryShortName());
            }
        }

        return $methods;
    }

    public function toString(): string
    {
        $static = $this->isStatic ? 'static' : '      ';

        if ($this->repository) {
            $returnType = "{$this->repository}|RepositoryProxy";
        } else {
            $returnType = match ($this->returnsCollection) {
                true => "{$this->objectName}[]|Proxy[]",
                false => "{$this->objectName}|Proxy",
            };
        }

        return " * @method {$static} {$returnType} {$this->prototype}";
    }

    public function sortValue(): string
    {
        return \sprintf(
            "returnsCollection:%s, prototype:{$this->prototype}",
            $this->returnsCollection ? '1' : '0',
        );
    }
}
