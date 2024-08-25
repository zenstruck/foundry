<?php

declare(strict_types=1);

namespace Zenstruck\Foundry\Tests\Fixture\InMemory;

use Zenstruck\Foundry\InMemory\AsInMemoryRepository;
use Zenstruck\Foundry\InMemory\InMemoryRepository;
use Zenstruck\Foundry\Tests\Fixture\Entity\Contact\StandardContact;

/**
 * @implements InMemoryRepository<StandardContact>
 */
#[AsInMemoryRepository(class: StandardContact::class)]
final class InMemoryStandardContactRepository implements InMemoryRepository
{
    /** @var list<StandardContact> */
    private array $elements = [];

    public function _save(object $element): void
    {
        if (!in_array($element, $this->elements, true)) {
            $this->elements[] = $element;
        }
    }

    public function _all(): array
    {
        return $this->elements;
    }
}
