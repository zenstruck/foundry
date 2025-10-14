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

namespace Zenstruck\Foundry\Tests\Integration\ResetDatabase;

use PHPUnit\Framework\Attributes\RequiresPhpunitExtension;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Attribute\ResetDatabase;
use Zenstruck\Foundry\PHPUnit\FoundryExtension;
use Zenstruck\Foundry\Tests\Fixture\Document\GlobalDocument;
use Zenstruck\Foundry\Tests\Fixture\Entity\GlobalEntity;
use Zenstruck\Foundry\Tests\Fixture\FoundryTestKernel;
use Zenstruck\Foundry\Tests\Fixture\Stories\GlobalStory;

use function Zenstruck\Foundry\Persistence\repository;

#[ResetDatabase]
#[RequiresPhpunitExtension(FoundryExtension::class)]
final class GlobalStoryTest extends GlobalStoryTestCase
{
}
