<?php

namespace Zenstruck\Foundry\Tests\Fixtures\Factories;

use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Tests\Fixtures\Entity\Cascade\Variant;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class CascadeVariantFactory extends ModelFactory
{
    protected static function getClass(): string
    {
        return Variant::class;
    }

    protected function getDefaults(): array
    {
        return [
            'name' => self::faker()->word(),
            'product' => CascadeProductFactory::new(),
        ];
    }

    protected function initialize(): self
    {
        return $this->disableCascadePersist();
    }
}
