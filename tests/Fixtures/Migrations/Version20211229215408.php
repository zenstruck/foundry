<?php

declare(strict_types=1);

namespace Zenstruck\Foundry\Tests\Fixtures\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20211229215408 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE entity_with_relations (id INT AUTO_INCREMENT NOT NULL, one_to_one_id INT NOT NULL, one_to_one_nullable_id INT DEFAULT NULL, many_to_one_id INT NOT NULL, many_to_one_nullable_id INT DEFAULT NULL, many_to_one_nullable_default_id INT DEFAULT NULL, UNIQUE INDEX UNIQ_A9C9EC96B549C760 (one_to_one_id), UNIQUE INDEX UNIQ_A9C9EC962C1E3906 (one_to_one_nullable_id), INDEX IDX_A9C9EC96EAB5DEB (many_to_one_id), INDEX IDX_A9C9EC96926C5B25 (many_to_one_nullable_id), INDEX IDX_A9C9EC968226E90E (many_to_one_nullable_default_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE entity_with_relations_category (entity_with_relations_id INT NOT NULL, category_id INT NOT NULL, INDEX IDX_22EACD458FACC72F (entity_with_relations_id), INDEX IDX_22EACD4512469DE2 (category_id), PRIMARY KEY(entity_with_relations_id, category_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE entity_with_relations ADD CONSTRAINT FK_A9C9EC96B549C760 FOREIGN KEY (one_to_one_id) REFERENCES categories (id)');
        $this->addSql('ALTER TABLE entity_with_relations ADD CONSTRAINT FK_A9C9EC962C1E3906 FOREIGN KEY (one_to_one_nullable_id) REFERENCES categories (id)');
        $this->addSql('ALTER TABLE entity_with_relations ADD CONSTRAINT FK_A9C9EC96EAB5DEB FOREIGN KEY (many_to_one_id) REFERENCES categories (id)');
        $this->addSql('ALTER TABLE entity_with_relations ADD CONSTRAINT FK_A9C9EC96926C5B25 FOREIGN KEY (many_to_one_nullable_id) REFERENCES categories (id)');
        $this->addSql('ALTER TABLE entity_with_relations ADD CONSTRAINT FK_A9C9EC968226E90E FOREIGN KEY (many_to_one_nullable_default_id) REFERENCES categories (id)');
        $this->addSql('ALTER TABLE entity_with_relations_category ADD CONSTRAINT FK_22EACD458FACC72F FOREIGN KEY (entity_with_relations_id) REFERENCES entity_with_relations (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE entity_with_relations_category ADD CONSTRAINT FK_22EACD4512469DE2 FOREIGN KEY (category_id) REFERENCES categories (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
    }
}
