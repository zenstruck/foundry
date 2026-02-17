<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Tests\Integration\Persistence;

use PHPUnit\Framework\Attributes\Before;

if (!\method_exists(Before::class, '__construct')) { // @phpstan-ignore function.alreadyNarrowedType
    trait GenericFactoryUsingBeforeHooksTrait
    {
        /** @before */
        #[Before]
        public function beforeSetup(): void
        {
            $this->setUp();
        }

        /** @before */
        #[Before]
        public function afterSetup(): void
        {
            $this->setUp();
        }
    }
} else {
    trait GenericFactoryUsingBeforeHooksTrait
    {
        #[Before(1)]
        public function beforeSetup(): void
        {
            $this->setUp();
        }

        #[Before(-1)]
        public function afterSetup(): void
        {
            $this->setUp();
        }
    }
}
