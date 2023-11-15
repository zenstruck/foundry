<?php

/*
 * This file is part of the zenstruck/assert package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Assert;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class AssertionFailed extends \RuntimeException
{
    private const MAX_SHORT_LENGTH = 100;

    /** @var array */
    private $context;

    public function __construct(string $message, array $context = [], ?\Throwable $previous = null)
    {
        $this->context = self::denormalizeContext($context);

        parent::__construct(self::createMessage($message, $context), 0, $previous);
    }

    /**
     * @return never
     */
    public function __invoke(): void
    {
        throw $this;
    }

    /**
     * Create and throw.
     *
     * @return never
     */
    public static function throw(string $message, array $context = [], ?\Throwable $previous = null): void
    {
        throw new self($message, $context, $previous);
    }

    public function context(): array
    {
        return $this->context;
    }

    private static function createMessage(string $template, array $context): string
    {
        // normalize context into scalar values
        $context = \array_map([self::class, 'normalizeContextValue'], $context);

        if (!$context) {
            return $template;
        }

        if (array_is_list($context)) {
            return \sprintf($template, ...$context);
        }

        return \strtr($template, self::normalizeContext($context));
    }

    /**
     * @param mixed $value
     */
    private static function normalizeContextValue($value): string
    {
        if (\is_object($value)) {
            return $value::class;
        }

        if (\is_array($value) && !$value) {
            return '(array:empty)';
        }

        if (\is_array($value)) {
            return array_is_list($value) ? '(array:list)' : '(array:assoc)';
        }

        if (!\is_scalar($value)) {
            return \sprintf('(%s)', \get_debug_type($value));
        }

        if (\is_bool($value)) {
            return \sprintf('(%s)', \var_export($value, true));
        }

        $value = (string) \preg_replace('/\s+/', ' ', (string) $value);

        if (\mb_strlen($value) <= self::MAX_SHORT_LENGTH) {
            return $value;
        }

        // shorten to max
        return \sprintf('%s...%s', \mb_substr($value, 0, self::MAX_SHORT_LENGTH - 40 - 3), \mb_substr($value, -40));
    }

    private static function normalizeContext(array $context): array
    {
        $newContext = [];

        foreach ($context as $key => $value) {
            if (!\preg_match('#^{.+}$#', $key)) {
                $key = "{{$key}}";
            }

            $newContext[$key] = $value;
        }

        return $newContext;
    }

    private static function denormalizeContext(array $context): array
    {
        $newContext = [];

        foreach ($context as $key => $value) {
            if (\preg_match('#^{(.+)}$#', $key, $matches)) {
                $key = $matches[1];
            }

            $newContext[$key] = $value;
        }

        return $newContext;
    }
}
