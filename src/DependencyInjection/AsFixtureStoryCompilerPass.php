<?php

namespace Zenstruck\Foundry\DependencyInjection;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\LogicException;
use Symfony\Component\DependencyInjection\Reference;

final class AsFixtureStoryCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->has('.zenstruck_foundry.story.load_story-command')) {
            return;
        }

        /** @var array<string, Reference> $fixtureStories */
        $fixtureStories = [];
        $groupedFixtureStories = [];
        foreach ($container->findTaggedServiceIds('foundry.story.fixture') as $id => $tags) {
            if (count($tags) !== 1) {
                throw new LogicException('Tag "foundry.story.fixture" must be used only once per service.');
            }

            $name = $tags[0]['name'];

            if (isset($fixtureStories[$name])) {
                throw new LogicException(
                    "Cannot use #[AsFixture] name \"{$name}\" for service \"{$id}\". This name is already used by service \"{$fixtureStories[$name]}\"."
                );
            }

            $storyClass = $container->findDefinition($id)->getClass();

            $fixtureStories[$name] = $storyClass;

            $groups = $tags[0]['groups'];
            if (!$groups) {
                continue;
            }

            foreach ($groups as $group) {
                $groupedFixtureStories[$group] ??= [];
                $groupedFixtureStories[$group][$name] = $storyClass;
            }
        }

        if ($collisionNames = array_intersect(array_keys($fixtureStories), array_keys($groupedFixtureStories))) {
            $collisionNames = implode('", "', $collisionNames);
            throw new LogicException(
                "Cannot use #[AsFixture] group(s) \"{$collisionNames}\", they collide with fixture names."
            );
        }

        $container->findDefinition('.zenstruck_foundry.story.load_story-command')
            ->setArgument('$stories', $fixtureStories)
            ->setArgument('$groupedStories', $groupedFixtureStories);
    }
}
