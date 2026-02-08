<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\ORM\ResetDatabase;

/**
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 */
enum ResetDatabaseMode: string
{
    case SCHEMA = 'schema';
    case MIGRATE = 'migrate';
}
