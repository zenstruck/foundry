<?php

declare(strict_types=1);

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Tests\Fixtures\Object;

use Zenstruck\Foundry\ObjectFactory;

/**
 * @extends ObjectFactory<SomeOtherObject>
 */
final class ObjectServiceFactory extends ObjectFactory
{
    public function __construct(
        private string $kernelProjectDir,
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
