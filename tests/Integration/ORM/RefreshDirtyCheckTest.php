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

namespace Zenstruck\Foundry\Tests\Integration\ORM;

use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Persistence\Exception\RefreshObjectFailed;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;
use Zenstruck\Foundry\Tests\Fixture\Entity\GenericEntity;
use Zenstruck\Foundry\Tests\Integration\RequiresORM;

use function Zenstruck\Foundry\Persistence\persistent_factory;
use function Zenstruck\Foundry\Persistence\refresh;

/**
 * The dirty-check of refresh() must not leave side effects in the UnitOfWork: computing
 * a change set on the real UOW overwrites the original-data snapshot, silently losing
 * any modification made before a refused refresh on the next flush.
 */
final class RefreshDirtyCheckTest extends KernelTestCase
{
    use Factories, RequiresORM, ResetDatabase;

    /** @test */
    #[Test]
    public function modifications_before_and_after_a_dirty_refresh_are_all_flushed(): void
    {
        $factory = persistent_factory(GenericEntity::class);
        $object = $factory->create(['prop1' => 'original', 'bool' => false]);

        $object->setProp1('changed');

        try {
            refresh($object);
            self::fail('refresh() should have refused: there are unsaved changes');
        } catch (RefreshObjectFailed) {
        }

        $object->bool = true;

        self::getContainer()->get(EntityManagerInterface::class)->flush(); // @phpstan-ignore method.notFound

        $factory::assert()->exists(['prop1' => 'changed', 'bool' => true]);
    }
}
