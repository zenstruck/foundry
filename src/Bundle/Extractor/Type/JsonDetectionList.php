<?php

namespace Zenstruck\Foundry\Bundle\Extractor\Type;

use function Zenstruck\Foundry\faker;

/**
 * Here we can detect fields on names which can be created with faker.
 * After we found no Constraints we need we can use this List to create better values then a sentence.
 */
class JsonDetectionList
{
    // @TODO add more fields
    public const LIST = [
      'roles' => ['ROLE_USER'],
    ];
}
