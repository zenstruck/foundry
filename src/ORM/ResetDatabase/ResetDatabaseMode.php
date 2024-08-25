<?php

declare(strict_types=1);

namespace Zenstruck\Foundry\ORM\ResetDatabase;

/**
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 */
enum ResetDatabaseMode: string
{
    case SCHEMA = 'schema';
    case MIGRATE = 'migrate';
}
