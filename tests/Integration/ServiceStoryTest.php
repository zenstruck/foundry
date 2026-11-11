<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Tests\Integration;

use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Tests\Fixture\Stories\UnregisteredServiceStory;

final class ServiceStoryTest extends KernelTestCase
{
    use Factories;

    /**
     * @test
     */
    #[Test]
    public function story_with_dependencies_which_is_not_a_service_throws_a_helpful_exception(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(\sprintf('Story "%s" has dependencies, so it must be a service tagged with "foundry.story".', UnregisteredServiceStory::class));

        UnregisteredServiceStory::load();
    }
}
