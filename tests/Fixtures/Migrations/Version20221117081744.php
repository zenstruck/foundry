<?php

declare(strict_types=1);

namespace Zenstruck\Foundry\Tests\Fixtures\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20221117081744 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Seventh migration.';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE entity_with_relations (id INT AUTO_INCREMENT NOT NULL, oneToOne_id INT NOT NULL, oneToOneNullable_id INT DEFAULT NULL, manyToOne_id INT NOT NULL, manyToOneNullable_id INT DEFAULT NULL, manyToOneNullableDefault_id INT DEFAULT NULL, manyToOneWithNotExistingFactory_id INT NOT NULL, UNIQUE INDEX UNIQ_A9C9EC969017888C (oneToOne_id), UNIQUE INDEX UNIQ_A9C9EC96DA2BFB84 (oneToOneNullable_id), INDEX IDX_A9C9EC962E3A088A (manyToOne_id), INDEX IDX_A9C9EC968097B86C (manyToOneNullable_id), INDEX IDX_A9C9EC968572C13C (manyToOneNullableDefault_id), INDEX IDX_A9C9EC96FF92FDCA (manyToOneWithNotExistingFactory_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE entitywithrelations_category (entitywithrelations_id INT NOT NULL, category_id INT NOT NULL, INDEX IDX_CD6EBFAB337AA4F7 (entitywithrelations_id), INDEX IDX_CD6EBFAB12469DE2 (category_id), PRIMARY KEY(entitywithrelations_id, category_id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE entity_with_relations ADD CONSTRAINT FK_A9C9EC969017888C FOREIGN KEY (oneToOne_id) REFERENCES categories (id)');
        $this->addSql('ALTER TABLE entity_with_relations ADD CONSTRAINT FK_A9C9EC96DA2BFB84 FOREIGN KEY (oneToOneNullable_id) REFERENCES categories (id)');
        $this->addSql('ALTER TABLE entity_with_relations ADD CONSTRAINT FK_A9C9EC962E3A088A FOREIGN KEY (manyToOne_id) REFERENCES categories (id)');
        $this->addSql('ALTER TABLE entity_with_relations ADD CONSTRAINT FK_A9C9EC968097B86C FOREIGN KEY (manyToOneNullable_id) REFERENCES categories (id)');
        $this->addSql('ALTER TABLE entity_with_relations ADD CONSTRAINT FK_A9C9EC968572C13C FOREIGN KEY (manyToOneNullableDefault_id) REFERENCES categories (id)');
        $this->addSql('ALTER TABLE entity_with_relations ADD CONSTRAINT FK_A9C9EC96FF92FDCA FOREIGN KEY (manyToOneWithNotExistingFactory_id) REFERENCES brand_cascade (id)');
        $this->addSql('ALTER TABLE entitywithrelations_category ADD CONSTRAINT FK_CD6EBFAB337AA4F7 FOREIGN KEY (entitywithrelations_id) REFERENCES entity_with_relations (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE entitywithrelations_category ADD CONSTRAINT FK_CD6EBFAB12469DE2 FOREIGN KEY (category_id) REFERENCES categories (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE entity_with_relations DROP FOREIGN KEY FK_A9C9EC969017888C');
        $this->addSql('ALTER TABLE entity_with_relations DROP FOREIGN KEY FK_A9C9EC96DA2BFB84');
        $this->addSql('ALTER TABLE entity_with_relations DROP FOREIGN KEY FK_A9C9EC962E3A088A');
        $this->addSql('ALTER TABLE entity_with_relations DROP FOREIGN KEY FK_A9C9EC968097B86C');
        $this->addSql('ALTER TABLE entity_with_relations DROP FOREIGN KEY FK_A9C9EC968572C13C');
        $this->addSql('ALTER TABLE entity_with_relations DROP FOREIGN KEY FK_A9C9EC96FF92FDCA');
        $this->addSql('ALTER TABLE entitywithrelations_category DROP FOREIGN KEY FK_CD6EBFAB337AA4F7');
        $this->addSql('ALTER TABLE entitywithrelations_category DROP FOREIGN KEY FK_CD6EBFAB12469DE2');
        $this->addSql('DROP TABLE entity_with_relations');
        $this->addSql('DROP TABLE entitywithrelations_category');
    }

    public function isTransactional(): bool
    {
        return false;
    }
}
