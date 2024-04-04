<?php

declare(strict_types=1);

namespace Zenstruck\Foundry\Tests\Fixtures\Object;

use Zenstruck\Foundry\ObjectFactory;

/**
 * @extends ObjectFactory<SomeOtherObject>
 */
final class ObjectServiceFactory extends ObjectFactory
{
    public function __construct(
        private string $kernelProjectDir
    ) {
        parent::__construct();
    }

    public static function class(): string
    {
        return SomeOtherObject::class;
    }

    protected function defaults(): array|callable
    {
        return [
            'foo' => $this->kernelProjectDir,
        ];
    }
}
