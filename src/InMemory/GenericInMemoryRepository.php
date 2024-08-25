<?php

declare(strict_types=1);

namespace Zenstruck\Foundry\InMemory;

/**
 * @template T of object
 * @implements InMemoryRepository<T>
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 *
 * This class will be used when a specific "in-memory" repository does not exist for a given class.
 */
final class GenericInMemoryRepository implements InMemoryRepository
{
    /**
     * @var list<T>
     */
    private array $elements = [];

    /**
     * @param class-string<T> $class
     */
    public function __construct(
        private readonly string $class
    )
    {
    }

    /**
     * @param T $element
     */
    public function _save(object $element): void
    {
        if (!$element instanceof $this->class) {
            throw new \InvalidArgumentException(sprintf('Given object of class "%s" is not an instance of expected "%s"', $element::class, $this->class));
        }

        if (!in_array($element, $this->elements, true)) {
            $this->elements[] = $element;
        }
    }

    public function _all(): array
    {
        return $this->elements;
    }
}
