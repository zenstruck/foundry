<?php

declare(strict_types=1);

namespace Zenstruck\Foundry\Tests\Fixture\InMemory;

use Zenstruck\Foundry\InMemory\AsInMemoryRepository;
use Zenstruck\Foundry\InMemory\InMemoryRepository;
use Zenstruck\Foundry\Tests\Fixture\Entity\Address\StandardAddress;

/**
 * @implements InMemoryRepository<StandardAddress>
 */
#[AsInMemoryRepository(class: StandardAddress::class)]
final class InMemoryStandardAddressRepository implements InMemoryRepository
{
    /**
     * @var list<StandardAddress>
     */
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
