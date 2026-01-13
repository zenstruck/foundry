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
use Zenstruck\Foundry\Test\Behat\FactoryResolver;

final class FactoryResolverTest extends TestCase
{
    #[Test]
    public function it_resolves_factory_by_auto_generated_name(): void
    {
        $resolver = new FactoryResolver([$factory = new PostFactory()]);

        self::assertSame($factory, $resolver->resolve('post'));
    }

    #[Test]
    public function it_resolves_factory_case_insensitively(): void
    {
        $resolver = new FactoryResolver([$factory = new PostFactory()]);

        self::assertSame($factory, $resolver->resolve('Post'));
        self::assertSame($factory, $resolver->resolve('POST'));
    }

    #[Test]
    public function it_resolves_factory_complex_short_name(): void
    {
        $resolver = new FactoryResolver([$factory = new BlogPostFactory()]);

        self::assertSame($factory, $resolver->resolve('blog post'));
        self::assertSame($factory, $resolver->resolve('BlOg PoSt'));
    }

    #[Test]
    public function it_uses_attribute_short_name(): void
    {
        $resolver = new FactoryResolver([$factory = new CustomNameFactory()]);

        self::assertSame($factory, $resolver->resolve('custom'));
    }

    #[Test]
    public function it_throws_when_factory_not_found(): void
    {
        $resolver = new FactoryResolver([new PostFactory()]);

        $this->expectException(FactoryNotResolvableException::class);
        $this->expectExceptionMessage('Cannot resolve factory for "unknown"');

        $resolver->resolve('unknown');
    }

    #[Test]
    #[DataProvider('factoriesWithConflictingShortNames')]
    public function it_throws_on_conflict(array $factories): void
    {
        $resolver = new FactoryResolver($factories);

        $this->expectException(FactoryNotResolvableException::class);
        $this->expectExceptionMessage('Multiple factories found for "article"');

        $resolver->resolve('article');
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
#[FactoryShortName('article')]
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

