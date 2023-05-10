<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Exception;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * @internal
 */
final class FoundryBootException extends \RuntimeException
{
    private function __construct(string $message)
    {
        parent::__construct($message);
    }

    public static function notBootedYet(): self
    {
        return new self(
            'Foundry is not yet booted. Using in a test: is your Test case using the Factories trait? Using in a fixture: is ZenstruckFoundryBundle enabled for this environment?'
        );
    }

    public static function notBootedWithDoctrine(): self
    {
        return new self(
            'Foundry was booted without doctrine. Ensure your TestCase extends '.KernelTestCase::class
        );
    }
}
