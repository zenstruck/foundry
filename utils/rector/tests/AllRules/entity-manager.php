<?php

declare(strict_types=1);

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\Driver\AttributeDriver;
use Doctrine\Persistence\Mapping\Driver\MappingDriverChain;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

$config = new Configuration();
$config->setProxyDir(__DIR__);
$config->setProxyNamespace('Zenstruck\Foundry\Utils\Rector\Tests\ChangeFactoryBaseClass\OrmProxies');
$config->setMetadataCache(new ArrayAdapter());

$metadataDriver = new MappingDriverChain();
$metadataDriver->addDriver(new AttributeDriver([]), 'Zenstruck\\Foundry\\Utils\\Rector\\Tests\\Fixtures\\');

$config->setMetadataDriverImpl($metadataDriver);

return EntityManager::create(
    [
        'driver' => 'pdo_sqlite',
        'memory' => true,
    ],
    $config
);
