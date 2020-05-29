<?php

namespace Zenstruck\Foundry\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Zenstruck\Foundry\Instantiator;
use Zenstruck\Foundry\Tests\Fixtures\Entity\Post;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class InstantiatorTest extends TestCase
{
    /**
     * @test
     */
    public function default_mode_calls_constructor_and_sets_properties(): void
    {
        /** @var Post $object */
        $object = Instantiator::default()(
            [
                'title' => 'title',
                'body' => 'body',
                'shortDescription' => 'description',
                'viewCount' => 3,
            ],
            PostForInstantiate::class
        );

        $this->assertInstanceOf(PostForInstantiate::class, $object);
        $this->assertSame('(constructor) title', $object->getTitle());
        $this->assertSame('(constructor) body', $object->getBody());
        $this->assertSame('(constructor) description', $object->getShortDescription());
        $this->assertSame(3, $object->getViewCount());
    }

    /**
     * @test
     */
    public function allows_snake_case_constructor_and_object_properties(): void
    {
        /** @var Post $object */
        $object = Instantiator::default()(
            [
                'title' => 'title',
                'body' => 'body',
                'short_description' => 'description',
                'view_count' => 3,
            ],
            PostForInstantiate::class
        );

        $this->assertInstanceOf(PostForInstantiate::class, $object);
        $this->assertSame('(constructor) title', $object->getTitle());
        $this->assertSame('(constructor) body', $object->getBody());
        $this->assertSame('(constructor) description', $object->getShortDescription());
        $this->assertSame(3, $object->getViewCount());
    }

    /**
     * @test
     */
    public function allows_kebab_case_constructor_and_object_properties(): void
    {
        /** @var Post $object */
        $object = Instantiator::default()(
            [
                'title' => 'title',
                'body' => 'body',
                'short-description' => 'description',
                'view-count' => 3,
            ],
            PostForInstantiate::class
        );

        $this->assertInstanceOf(PostForInstantiate::class, $object);
        $this->assertSame('(constructor) title', $object->getTitle());
        $this->assertSame('(constructor) body', $object->getBody());
        $this->assertSame('(constructor) description', $object->getShortDescription());
        $this->assertSame(3, $object->getViewCount());
    }

    /**
     * @test
     */
    public function allows_default_constructor_parameters(): void
    {
        /** @var Post $object */
        $object = Instantiator::default()(
            [
                'title' => 'title',
                'body' => 'body',
            ],
            PostForInstantiate::class
        );

        $this->assertInstanceOf(PostForInstantiate::class, $object);
        $this->assertSame('(constructor) title', $object->getTitle());
        $this->assertSame('(constructor) body', $object->getBody());
        $this->assertNull($object->getShortDescription());
        $this->assertSame(0, $object->getViewCount());
    }

    /**
     * @test
     */
    public function allows_extra_attributes_by_default(): void
    {
        /** @var Post $object */
        $object = Instantiator::default()(
            [
                'title' => 'title',
                'body' => 'body',
                'extra' => 'foo',
            ],
            PostForInstantiate::class
        );

        $this->assertInstanceOf(PostForInstantiate::class, $object);
    }

    /**
     * @test
     */
    public function strict_mode_does_not_allow_extra_attributes(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(\sprintf('Class "%s" does not have property "extra"', PostForInstantiate::class));

        Instantiator::default()->strict()(
            [
                'title' => 'title',
                'body' => 'body',
                'extra' => 'foo',
            ],
            PostForInstantiate::class
        );
    }

    /**
     * @test
     */
    public function default_mode_throws_exception_if_missing_constructor_argument(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(\sprintf('Missing constructor argument "body" for "%s"', PostForInstantiate::class));

        Instantiator::default()(['title' => 'title'], PostForInstantiate::class);
    }

    /**
     * @test
     */
    public function only_constructor_mode_throws_exception_if_missing_constructor_argument(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(\sprintf('Missing constructor argument "body" for "%s"', PostForInstantiate::class));

        Instantiator::onlyConstructor()(['title' => 'title'], PostForInstantiate::class);
    }

    /**
     * @test
     */
    public function without_constructor_mode_bypasses_constructor(): void
    {
        /** @var Post $object */
        $object = Instantiator::withoutConstructor()(
            [
                'title' => 'title',
            ],
            PostForInstantiate::class
        );

        $this->assertInstanceOf(PostForInstantiate::class, $object);
        $this->assertSame('title', $object->getTitle());
        $this->assertNull($object->getBody());
    }

    /**
     * @test
     */
    public function only_constructor_mode_does_not_set_properties(): void
    {
        /** @var Post $object */
        $object = Instantiator::onlyConstructor()(
            [
                'title' => 'title',
                'body' => 'body',
                'shortDescription' => 'description',
                'viewCount' => 3,
            ],
            PostForInstantiate::class
        );

        $this->assertInstanceOf(PostForInstantiate::class, $object);
        $this->assertSame('(constructor) title', $object->getTitle());
        $this->assertSame('(constructor) body', $object->getBody());
        $this->assertSame('(constructor) description', $object->getShortDescription());
        $this->assertSame(0, $object->getViewCount());
    }

    /**
     * @test
     */
    public function can_force_set_non_public_properties(): void
    {
        $post = new PostForInstantiate('title', 'body');

        $this->assertSame('(constructor) title', $post->getTitle());

        Instantiator::default()->forceSet($post, 'title', 'new title');
        Instantiator::default()->forceSet($post, 'missing', 'foobar');

        $this->assertSame('new title', $post->getTitle());
    }

    /**
     * @test
     */
    public function force_set_with_missing_attribute_and_strict_mode_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(\sprintf('Class "%s" does not have property "missing"', PostForInstantiate::class));

        Instantiator::default()->strict()->forceSet(new PostForInstantiate('title', 'body'), 'missing', 'foobar');
    }

    /**
     * @test
     */
    public function can_force_get_non_public_properties(): void
    {
        $post = new PostForInstantiate('title', 'body');

        $this->assertSame('(constructor) title', Instantiator::default()->forceGet($post, 'title'));
        $this->assertNull(Instantiator::default()->forceGet($post, 'missing'));
    }

    /**
     * @test
     */
    public function force_get_with_missing_attribute_and_strict_mode_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(\sprintf('Class "%s" does not have property "missing"', PostForInstantiate::class));

        Instantiator::default()->strict()->forceGet(new PostForInstantiate('title', 'body'), 'missing');
    }
}

class PostForInstantiate extends Post
{
    public function __construct(string $title, string $body, string $shortDescription = null)
    {
        $title = '(constructor) '.$title;
        $body = '(constructor) '.$body;

        if ($shortDescription) {
            $shortDescription = '(constructor) '.$shortDescription;
        }

        parent::__construct($title, $body, $shortDescription);
    }
}
