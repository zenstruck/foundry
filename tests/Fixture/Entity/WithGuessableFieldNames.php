<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Tests\Fixture\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Zenstruck\Foundry\Tests\Fixture\Model\Base;

#[ORM\Entity]
class WithGuessableFieldNames extends Base
{
    public function __construct(
        #[ORM\Column(length: 255)]
        public string $firstName,

        #[ORM\Column(length: 255)]
        public string $lastName,

        // too short to store any username
        #[ORM\Column(length: 20)]
        public string $username,

        #[ORM\Column(length: 180)]
        public string $email,

        #[ORM\Column(length: 255)]
        public string $phone_number,

        #[ORM\Column(length: 255)]
        public string $job,

        #[ORM\Column(length: 255, nullable: true)]
        public ?string $company,

        #[ORM\Column(length: 255)]
        public string $cityName,

        // not a string
        #[ORM\Column]
        public int $postcode,

        #[ORM\Column(length: 255)]
        public string $country,

        #[ORM\Column(length: 2)]
        public string $countryCode,

        #[ORM\Column]
        public float $latitude,

        #[ORM\Column(type: Types::DECIMAL, precision: 9, scale: 6)]
        public string $longitude,
    ) {
    }
}
