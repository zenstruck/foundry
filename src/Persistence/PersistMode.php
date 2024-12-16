<?php

declare(strict_types=1);

namespace Zenstruck\Foundry\Persistence;

/**
 * @internal
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 */
enum PersistMode
{
    case PERSIST;
    case WITHOUT_PERSISTING;
    case NO_PERSIST_BUT_SCHEDULE_FOR_INSERT;

    public function isPersisting(): bool
    {
        return $this === self::PERSIST;
    }
}
