<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Zenstruck\Foundry\Tests\Fixture\Stories\ServiceStory;

final class ServiceStoryTest extends TestCase
{
    #[Test]
    public function story_with_dependencies_cannot_be_used_without_the_bundle(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Stories with dependencies (Story services) cannot be used without the foundry bundle.');

        ServiceStory::load();
    }
}
