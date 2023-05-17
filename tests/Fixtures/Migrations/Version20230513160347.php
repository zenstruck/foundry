<?php

declare(strict_types=1);

namespace Zenstruck\Foundry\Tests\Fixtures\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230513160347 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'third migration for php 8.1 enums';
    }

    public function up(Schema $schema): void
    {
        if (PHP_VERSION_ID < 80100) {
            return;
        }

        $this->addSql('CREATE SEQUENCE entity_with_enum_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE entity_with_enum (id INT NOT NULL, enum VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
    }

    public function down(Schema $schema): void
    {
        if (PHP_VERSION_ID < 80100) {
            return;
        }

        $this->addSql('DROP SEQUENCE entity_with_enum_id_seq CASCADE');
        $this->addSql('DROP TABLE entity_with_enum');
    }
}
