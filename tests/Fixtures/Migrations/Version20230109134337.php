<?php

declare(strict_types=1);

namespace Zenstruck\Foundry\Tests\Fixtures\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230109134337 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add table "entity_with_enum"';
    }

    public function up(Schema $schema): void
    {
        if (PHP_VERSION_ID < 80100) {
            return;
        }

        $this->addSql('CREATE TABLE entity_with_enum (id INT AUTO_INCREMENT NOT NULL, enum VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        if (PHP_VERSION_ID < 80100) {
            return;
        }

        $this->addSql('DROP TABLE entity_with_enum');
    }
}
