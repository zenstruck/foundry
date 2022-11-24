<?php

declare(strict_types=1);

namespace Zenstruck\Foundry\Tests\Fixtures\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20221124123656 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Eighth migration.';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE entity_for_relations (id INT AUTO_INCREMENT NOT NULL, manyToOne_id INT DEFAULT NULL, INDEX IDX_C63B81552E3A088A (manyToOne_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE entity_for_relations ADD CONSTRAINT FK_C63B81552E3A088A FOREIGN KEY (manyToOne_id) REFERENCES entity_with_relations (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE entity_for_relations DROP FOREIGN KEY FK_C63B81552E3A088A');
        $this->addSql('DROP TABLE entity_for_relations');
    }

    public function isTransactional(): bool
    {
        return false;
    }
}
