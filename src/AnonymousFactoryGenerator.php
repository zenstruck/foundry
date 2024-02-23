<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 */
final class AnonymousFactoryGenerator
{
    /**
     * @template T of object
     * @template F of Factory<T>
     *
     * @param class-string<T> $class
     * @param class-string<F> $factoryClass
     *
     * @return class-string<F>
     */
    public static function create(string $class, string $factoryClass): string
    {
        $anonymousClassName = \sprintf('FoundryAnonymous%s_', (new \ReflectionClass($factoryClass))->getShortName());
        $anonymousClassName .= \str_replace('\\', '', $class);
        $anonymousClassName = \preg_replace('/\W/', '', $anonymousClassName); // sanitize for anonymous classes

        /** @var class-string<F> $anonymousClassName */
        if (!\class_exists($anonymousClassName)) {
            $anonymousClassCode = <<<CODE
                /**
                 * @internal
                 */
                final class {$anonymousClassName} extends {$factoryClass}
                {
                    public static function class(): string
                    {
                        return "{$class}";
                    }

                    protected function defaults(): array
                    {
                        return [];
                    }
                }
                CODE;

            eval($anonymousClassCode); // @phpstan-ignore-line
        }

        return $anonymousClassName;
    }
}
