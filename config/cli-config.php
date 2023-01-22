<?php

require 'vendor/autoload.php';

use Doctrine\Migrations\Configuration\EntityManager\ExistingEntityManager;
use Doctrine\Migrations\Configuration\Migration\ConfigurationArray;
use Doctrine\Migrations\DependencyFactory;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;

$entities = ['/app/tests/Fixtures/Entity'];
if (PHP_VERSION_ID >= 80100) {
    $entities[] = '/app/tests/Fixtures/PHP81';
}
$ORMconfig = ORMSetup::createAttributeMetadataConfiguration($entities, true);
$entityManager = EntityManager::create(['memory' => true, 'url' => getenv('DATABASE_URL')], $ORMconfig);

return DependencyFactory::fromEntityManager(
    new ConfigurationArray([
        'table_storage' => [
            'table_name' => 'doctrine_migration_versions',
        ],

        'migrations_paths' => [
            'Zenstruck\Foundry\Tests\Fixtures\Migrations' => './tests/Fixtures/Migrations',
        ],
    ]),
    new ExistingEntityManager($entityManager)
);
