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

namespace Zenstruck\Foundry\Tests\Unit\Test\Behat;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RequiresPhp;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Zenstruck\Foundry\Attribute\FactoryShortName;
use Zenstruck\Foundry\ObjectFactory;
use Zenstruck\Foundry\Test\Behat\Exception\FactoryNotResolvable;
use Zenstruck\Foundry\Test\Behat\FactoryShortNameResolver;

/** @requires PHP 9 */
#[RequiresPhp('9')]
final class FactoryResolverTest extends TestCase
{
    public static function factoriesWithConflictingShortNames(): iterable
    {
        yield 'same short name in attribute' => [[new Article1Factory(), new Article2Factory()]];
        yield 'same generated short name' => [[new Article1Factory(), new ArticleFactory()]];
    }

    #[Test]
    public function it_resolves_factory_by_auto_generated_name(): void
    {
        $resolver = new FactoryShortNameResolver([$factory = new PostFactory()]);

        self::assertInstanceOf($factory::class, $resolver->factoryFor('post'));
        self::assertInstanceOf($factory::class, $resolver->factoryFor('posts'));
    }

    #[Test]
    public function it_resolves_factory_case_insensitively(): void
    {
        $resolver = new FactoryShortNameResolver([$factory = new PostFactory()]);

        self::assertInstanceOf($factory::class, $resolver->factoryFor('Post'));
        self::assertInstanceOf($factory::class, $resolver->factoryFor('POST'));
    }

    #[Test]
    public function it_resolves_factory_complex_short_name(): void
    {
        $resolver = new FactoryShortNameResolver([$factory = new BlogPostFactory()]);

        self::assertInstanceOf($factory::class, $resolver->factoryFor('blog post'));
        self::assertInstanceOf($factory::class, $resolver->factoryFor('blog posts'));

        self::assertInstanceOf($factory::class, $resolver->factoryFor('BlOg PoSt'));
        self::assertInstanceOf($factory::class, $resolver->factoryFor('BlOg PoSts'));
    }

    #[Test]
    public function it_uses_attribute_short_name(): void
    {
        $resolver = new FactoryShortNameResolver([$factory = new CustomNameFactory()]);

        self::assertInstanceOf($factory::class, $resolver->factoryFor('custom'));
        self::assertInstanceOf($factory::class, $resolver->factoryFor('customs'));
    }

    #[Test]
    public function it_can_resolve_with_custom_plural_form(): void
    {
        $resolver = new FactoryShortNameResolver([$factory = new Article1Factory()]);

        self::assertInstanceOf($factory::class, $resolver->factoryFor('several articles'));
    }

    #[Test]
    public function it_throws_when_factory_not_found(): void
    {
        $resolver = new FactoryShortNameResolver([new PostFactory()]);

        $this->expectException(FactoryNotResolvable::class);
        $this->expectExceptionMessage('Cannot resolve factory for name "unknown"');

        $resolver->factoryFor('unknown');
    }

    #[Test]
    #[DataProvider('factoriesWithConflictingShortNames')]
    public function it_throws_on_conflict(array $factories): void
    {
        $resolver = new FactoryShortNameResolver($factories);

        $this->expectException(FactoryNotResolvable::class);
        $this->expectExceptionMessage('Multiple factories found for name "article"');

        $resolver->factoryFor('article');
    }

    #[Test]
    public function it_checks_if_factory_exists_for_class(): void
    {
        $resolver = new FactoryShortNameResolver([new PostFactory()]);

        self::assertTrue($resolver->hasFactoryForClass(\stdClass::class));
        self::assertFalse($resolver->hasFactoryForClass(\DateTime::class));
    }

    #[Test]
    public function it_gets_short_name_for_class(): void
    {
        $resolver = new FactoryShortNameResolver([new PostFactory()]);

        self::assertSame('post', $resolver->getShortNameForClass(\stdClass::class));
    }

    #[Test]
    public function it_gets_short_name_for_class_with_custom_attribute(): void
    {
        $resolver = new FactoryShortNameResolver([new CustomNameFactory()]);

        self::assertSame('custom', $resolver->getShortNameForClass(\stdClass::class));
    }
}

/** @extends ObjectFactory<\stdClass> */
final class PostFactory extends ObjectFactory
{
    public static function class(): string
    {
        return \stdClass::class;
    }

    protected function defaults(): array
    {
        return [];
    }
}

/** @extends ObjectFactory<\stdClass> */
#[FactoryShortName('custom')]
final class CustomNameFactory extends ObjectFactory
{
    public static function class(): string
    {
        return \stdClass::class;
    }

    protected function defaults(): array
    {
        return [];
    }
}

/** @extends ObjectFactory<\stdClass> */
final class BlogPostFactory extends ObjectFactory
{
    public static function class(): string
    {
        return \stdClass::class;
    }

    protected function defaults(): array
    {
        return [];
    }
}

/** @extends ObjectFactory<\stdClass> */
final class ArticleFactory extends ObjectFactory
{
    public static function class(): string
    {
        return \stdClass::class;
    }

    protected function defaults(): array
    {
        return [];
    }
}

/** @extends ObjectFactory<\stdClass> */
#[FactoryShortName('article', 'several articles')]
final class Article1Factory extends ObjectFactory
{
    public static function class(): string
    {
        return \stdClass::class;
    }

    protected function defaults(): array
    {
        return [];
    }
}

/** @extends ObjectFactory<\stdClass> */
#[FactoryShortName('article')]
final class Article2Factory extends ObjectFactory
{
    public static function class(): string
    {
        return \stdClass::class;
    }

    protected function defaults(): array
    {
        return [];
    }
}
