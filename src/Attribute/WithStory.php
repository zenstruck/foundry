<?php

declare(strict_types=1);

namespace Zenstruck\Foundry\Attribute;

use Zenstruck\Foundry\Story;

#[\Attribute(\Attribute::IS_REPEATABLE | \Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD)]
final class WithStory
{
    public function __construct(
        /** @var class-string<Story> $story */
        public readonly string $story
    )
    {
        if (!\is_subclass_of($story, Story::class)) {
            throw new \InvalidArgumentException(\sprintf('"%s" is not a valid story class.', $story));
        }
    }
}
