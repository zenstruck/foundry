<?php

declare(strict_types=1);

namespace Zenstruck\Foundry\ORM\ResetDatabase;

use Zenstruck\Foundry\Persistence\ResetDatabase\BeforeFirstTestResetter;
use Zenstruck\Foundry\Persistence\ResetDatabase\BeforeEachTestResetter;

/**
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 */
interface OrmResetter extends BeforeFirstTestResetter, BeforeEachTestResetter
{
}
