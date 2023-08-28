<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry;

use Zenstruck\Foundry\Object\ObjectFactory;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @template TObject of object
 * @template-extends PersistentObjectFactory<TObject>
 *
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @deprecated since 1.36, use "Zenstruck\Foundry\Persistence\PersistentObjectFactory" instead
 */
abstract class Factory extends PersistentObjectFactory
{
    /**
     * @param class-string<TObject> $class
     */
    public function __construct(string $class, array|callable $defaultAttributes = [])
    {
        trigger_deprecation('zenstruck/foundry', '1.36', '"%s" is deprecated and this class will be removed in 2.0, please use "%s" or "%s" instead.', self::class, PersistentObjectFactory::class, ObjectFactory::class);

        /** @phpstan-ignore-next-line */
        if (self::class === static::class) {
            trigger_deprecation('zenstruck/foundry', '1.9', 'Instantiating "%s" directly is deprecated and this class will be abstract in 2.0, use "anonymous()" function instead.', self::class);
        }
    }
}
