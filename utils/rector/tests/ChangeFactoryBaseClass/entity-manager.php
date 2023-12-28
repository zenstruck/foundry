<?php
declare(strict_types=1);

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
