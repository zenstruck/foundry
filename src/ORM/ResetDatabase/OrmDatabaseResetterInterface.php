<?php

declare(strict_types=1);

namespace Zenstruck\Foundry\ORM\ResetDatabase;

use Zenstruck\Foundry\Persistence\ResetDatabase\DatabaseResetterInterface;
use Zenstruck\Foundry\Persistence\ResetDatabase\SchemaResetterInterface;

interface OrmDatabaseResetterInterface extends DatabaseResetterInterface, SchemaResetterInterface
{
}
