<?php

declare(strict_types=1);

namespace Zenstruck\Foundry\Tests\Fixtures\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20221010154036 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Sixth migration.';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE posts CHANGE type type VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE posts RENAME INDEX idx_985dbafad126f52 TO IDX_885DBAFA20DBE482');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE posts CHANGE type type VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT \'simple\' NOT NULL COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE posts RENAME INDEX idx_885dbafa20dbe482 TO IDX_985DBAFAD126F52');
    }
}
