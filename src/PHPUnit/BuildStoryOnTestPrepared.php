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

namespace Zenstruck\Foundry\PHPUnit;

use PHPUnit\Event;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Attribute\WithStory;
use Zenstruck\Foundry\Exception\FactoriesTraitNotUsed;

/**
 * @internal
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 */
final class BuildStoryOnTestPrepared implements Event\Test\PreparedSubscriber
{
    public function notify(Event\Test\Prepared $event): void
    {
        $test = $event->test();

        if (!$test->isTestMethod()) {
            return;
        }

        /** @var Event\Code\TestMethod $test */
        $reflectionClass = new \ReflectionClass($test->className());
        $withStoryAttributes = [
            ...$this->collectWithStoryAttributesFromClassAndParents($reflectionClass),
            ...$reflectionClass->getMethod($test->methodName())->getAttributes(WithStory::class),
        ];

        if (!$withStoryAttributes) {
            return;
        }

        if (!\is_subclass_of($test->className(), KernelTestCase::class)) {
            throw new \InvalidArgumentException(\sprintf('The test class "%s" must extend "%s" to use the "%s" attribute.', $test->className(), KernelTestCase::class, WithStory::class));
        }

        FactoriesTraitNotUsed::throwIfClassDoesNotHaveFactoriesTrait($test->className());

        foreach ($withStoryAttributes as $withStoryAttribute) {
            $withStoryAttribute->newInstance()->story::load();
        }
    }

    /**
     * @return list<\ReflectionAttribute<WithStory>>
     */
    private function collectWithStoryAttributesFromClassAndParents(\ReflectionClass $class): array // @phpstan-ignore missingType.generics
    {
        return [
            ...$class->getAttributes(WithStory::class),
            ...(
                $class->getParentClass()
                    ? $this->collectWithStoryAttributesFromClassAndParents($class->getParentClass())
                    : []
            ),
        ];
    }
}
