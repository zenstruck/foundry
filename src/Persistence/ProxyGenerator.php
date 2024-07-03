<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Persistence;

use Doctrine\Persistence\Proxy as DoctrineProxy;
use Symfony\Component\VarExporter\LazyObjectInterface;
use Symfony\Component\VarExporter\LazyProxyTrait;
use Symfony\Component\VarExporter\ProxyHelper;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 */
final class ProxyGenerator
{
    private function __construct()
    {
    }

    /**
     * @template T of object
     *
     * @param T $object
     *
     * @return T&Proxy<T>
     */
    public static function wrap(object $object): Proxy
    {
        if ($object instanceof Proxy) {
            return $object;
        }

        return self::generateClassFor($object)::createLazyProxy(static fn() => $object); // @phpstan-ignore-line
    }

    /**
     * @template T
     *
     * @param T $what
     *
     * @return T
     */
    public static function unwrap(mixed $what): mixed
    {
        if (\is_array($what)) {
            return \array_map(self::unwrap(...), $what); // @phpstan-ignore-line
        }

        if (\is_string($what) && \is_a($what, Proxy::class, true)) {
            return \get_parent_class($what) ?: throw new \LogicException('Could not unwrap proxy.'); // @phpstan-ignore-line
        }

        if ($what instanceof Proxy) {
            return $what->_real(); // @phpstan-ignore-line
        }

        return $what;
    }

    /**
     * @template T of object
     *
     * @param T $object
     *
     * @return class-string<LazyObjectInterface&Proxy<T>&T>
     */
    private static function generateClassFor(object $object): string
    {
        /** @var class-string $class */
        $class = $object instanceof DoctrineProxy ? \get_parent_class($object) : $object::class;
        $proxyClass = self::proxyClassNameFor($class);

        /** @var class-string<LazyObjectInterface&Proxy<T>&T> $proxyClass */
        if (\class_exists($proxyClass, autoload: false)) {
            return $proxyClass;
        }

        $proxyCode = 'class '.$proxyClass.ProxyHelper::generateLazyProxy($reflectionClass = new \ReflectionClass($class));
        $proxyCode = \strtr(
            $proxyCode,
            [
                'implements \Symfony\Component\VarExporter\LazyObjectInterface' => \sprintf('implements \%s, \Symfony\Component\VarExporter\LazyObjectInterface', Proxy::class),
                'use \Symfony\Component\VarExporter\LazyProxyTrait' => \sprintf("use \\%s;\n    use \\%s", IsProxy::class, LazyProxyTrait::class),
                'if (isset($this->lazyObjectState)) {' => "\$this->_autoRefresh();\n\n        if (isset(\$this->lazyObjectReal)) {",
                '\func_get_args()' => '$this->unproxyArgs(\func_get_args())',
            ],
        );

        eval($proxyCode); // @phpstan-ignore-line

        return $proxyClass;
    }

    /**
     * @param class-string $class
     */
    public static function proxyClassNameFor(string $class): string
    {
        return \str_replace('\\', '', $class).'Proxy';
    }
}
