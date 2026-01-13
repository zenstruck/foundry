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
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Zenstruck\Foundry\Attribute\FactoryShortName;
use Zenstruck\Foundry\Factory;
use Zenstruck\Foundry\FactoryRegistry;
use Zenstruck\Foundry\ObjectFactory;
use Zenstruck\Foundry\Test\Behat\FactoryNotResolvableException;
use Zenstruck\Foundry\Test\Behat\FactoryShortNameResolver;

final class FactoryResolverTest extends TestCase
{
    #[Test]
    public function it_resolves_factory_by_auto_generated_name(): void
    {
        $resolver = new FactoryShortNameResolver([$factory = new PostFactory()]);

        self::assertSame($factory, $resolver->factoryFor('post'));
        self::assertSame($factory, $resolver->factoryFor('posts'));
    }

    #[Test]
    public function it_resolves_factory_case_insensitively(): void
    {
        $resolver = new FactoryShortNameResolver([$factory = new PostFactory()]);

        self::assertSame($factory, $resolver->factoryFor('Post'));
        self::assertSame($factory, $resolver->factoryFor('POST'));
    }

    #[Test]
    public function it_resolves_factory_complex_short_name(): void
    {
        $resolver = new FactoryShortNameResolver([$factory = new BlogPostFactory()]);

        self::assertSame($factory, $resolver->factoryFor('blog post'));
        self::assertSame($factory, $resolver->factoryFor('blog posts'));

        self::assertSame($factory, $resolver->factoryFor('BlOg PoSt'));
        self::assertSame($factory, $resolver->factoryFor('BlOg PoSts'));
    }

    #[Test]
    public function it_uses_attribute_short_name(): void
    {
        $resolver = new FactoryShortNameResolver([$factory = new CustomNameFactory()]);

        self::assertSame($factory, $resolver->factoryFor('custom'));
        self::assertSame($factory, $resolver->factoryFor('customs'));
    }

    #[Test]
    public function it_can_resolve_with_custom_plural_form(): void
    {
        $resolver = new FactoryShortNameResolver([$factory = new Article1Factory()]);

        self::assertSame($factory, $resolver->factoryFor('several articles'));
    }

    #[Test]
    public function it_throws_when_factory_not_found(): void
    {
        $resolver = new FactoryShortNameResolver([new PostFactory()]);

        $this->expectException(FactoryNotResolvableException::class);
        $this->expectExceptionMessage('Cannot resolve factory for "unknown"');

        $resolver->factoryFor('unknown');
    }

    #[Test]
    #[DataProvider('factoriesWithConflictingShortNames')]
    public function it_throws_on_conflict(array $factories): void
    {
        $resolver = new FactoryShortNameResolver($factories);

        $this->expectException(FactoryNotResolvableException::class);
        $this->expectExceptionMessage('Multiple factories found for "article"');

        $resolver->factoryFor('article');
    }

    public static function factoriesWithConflictingShortNames(): iterable
    {
        yield 'same short name in attribute' => [[new Article1Factory(), new Article2Factory()]];
        yield 'same generated short name' => [[new Article1Factory(), new ArticleFactory()]];
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

