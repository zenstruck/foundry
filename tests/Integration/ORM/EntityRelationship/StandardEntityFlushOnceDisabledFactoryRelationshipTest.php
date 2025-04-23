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

namespace Zenstruck\Foundry\Tests\Integration\ORM\EntityRelationship;

use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\Attributes\RequiresPhpunit;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 * @requires PHPUnit >=11.4
 */
#[RequiresPhpunit('>=11.4')]
#[IgnoreDeprecations]
final class StandardEntityFlushOnceDisabledFactoryRelationshipTest extends StandardEntityFactoryRelationshipTest
{
    protected static function bootKernel(array $options = []): KernelInterface
    {
        return parent::bootKernel(['environment' => 'disable_flush_once'] + $options);
    }
}
