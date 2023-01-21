<?php

declare(strict_types=1);

namespace Zenstruck\Foundry\Tests\Fixtures\PHP81;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;

#[MongoDB\Document(collection: "document-with-enum")]
class DocumentWithEnum
{
    #[MongoDB\Id]
    private $id;

    #[MongoDB\Field(enumType: SomeEnum::class)]
    public SomeEnum $enum;
}
