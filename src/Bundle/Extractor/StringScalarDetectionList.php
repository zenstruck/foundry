<?php

namespace Zenstruck\Foundry\Bundle\Extractor;

use function Zenstruck\Foundry\faker;

/**
 * Here we can detect fields on names which can be created with faker.
 * After we found no Constraints we need we can use this List to create better values then a sentence.
 */
class StringScalarDetectionList
{
    public const SCALAR_DETECTION_LIST = [
      'company' => 'company',
      'name' => 'name',
      'firstName' => 'firstName',
      'email' => 'email',
      'url' => 'url',
      'password' => 'password',
      'title' => 'title',
      'address' => 'address',
      'bankAccountNumber' => 'bankAccountNumber',
      'city' => 'city',
      'colorName' => 'colorName',
      'companyEmail' => 'companyEmail',
      'country' => 'country',
      'countryCode' => 'countryCode',
      'creditcardNumber' => 'creditcardNumber',
      'currencyCode' => 'currencCode',
      'jobTitle' => 'jobTitle',
      'lastName' => 'lastName',
      'locale' => 'locale',
      'postcode' => 'postcode',
      'userName' => 'userName',
      'displayName' => 'name',
    ];
}
