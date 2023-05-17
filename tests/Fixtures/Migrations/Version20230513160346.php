<?php

declare(strict_types=1);

namespace Zenstruck\Foundry\Tests\Fixtures\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230513160346 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'second migration';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE SEQUENCE brand_cascade_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE category_cascade_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE image_cascade_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE product_cascade_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE review_cascade_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE tag_cascade_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE variant_cascade_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE brand_cascade (id INT NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE category_cascade (id INT NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE image_cascade (id INT NOT NULL, path VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE product_cascade (id INT NOT NULL, brand_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_D7FE16D844F5D008 ON product_cascade (brand_id)');
        $this->addSql('CREATE TABLE review_cascade (id INT NOT NULL, product_id INT DEFAULT NULL, ranking INT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_9DC9B99F4584665A ON review_cascade (product_id)');
        $this->addSql('CREATE TABLE tag_cascade (id INT NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE variant_cascade (id INT NOT NULL, product_id INT DEFAULT NULL, image_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_6982202E4584665A ON variant_cascade (product_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_6982202E3DA5256D ON variant_cascade (image_id)');
        $this->addSql('ALTER TABLE product_cascade ADD CONSTRAINT FK_D7FE16D844F5D008 FOREIGN KEY (brand_id) REFERENCES brand_cascade (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE review_cascade ADD CONSTRAINT FK_9DC9B99F4584665A FOREIGN KEY (product_id) REFERENCES product_cascade (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE variant_cascade ADD CONSTRAINT FK_6982202E4584665A FOREIGN KEY (product_id) REFERENCES product_cascade (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE variant_cascade ADD CONSTRAINT FK_6982202E3DA5256D FOREIGN KEY (image_id) REFERENCES image_cascade (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE productcategory_product ADD CONSTRAINT FK_5BC2A6A2E26A32B1 FOREIGN KEY (productcategory_id) REFERENCES category_cascade (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE productcategory_product ADD CONSTRAINT FK_5BC2A6A24584665A FOREIGN KEY (product_id) REFERENCES product_cascade (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE entity_with_relations ADD CONSTRAINT FK_A9C9EC96FF92FDCA FOREIGN KEY (manyToOneWithNotExistingFactory_id) REFERENCES brand_cascade (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE product_tag ADD CONSTRAINT FK_E3A6E39C4584665A FOREIGN KEY (product_id) REFERENCES product_cascade (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE product_tag ADD CONSTRAINT FK_E3A6E39CBAD26311 FOREIGN KEY (tag_id) REFERENCES tag_cascade (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP SEQUENCE brand_cascade_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE category_cascade_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE image_cascade_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE product_cascade_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE review_cascade_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE tag_cascade_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE variant_cascade_id_seq CASCADE');
        $this->addSql('ALTER TABLE product_cascade DROP CONSTRAINT FK_D7FE16D844F5D008');
        $this->addSql('ALTER TABLE review_cascade DROP CONSTRAINT FK_9DC9B99F4584665A');
        $this->addSql('ALTER TABLE variant_cascade DROP CONSTRAINT FK_6982202E4584665A');
        $this->addSql('ALTER TABLE variant_cascade DROP CONSTRAINT FK_6982202E3DA5256D');
        $this->addSql('ALTER TABLE entity_with_relations DROP CONSTRAINT FK_A9C9EC96FF92FDCA');
        $this->addSql('ALTER TABLE productcategory_product DROP CONSTRAINT FK_5BC2A6A2E26A32B1');
        $this->addSql('ALTER TABLE productcategory_product DROP CONSTRAINT FK_5BC2A6A24584665A');
        $this->addSql('ALTER TABLE product_tag DROP CONSTRAINT FK_E3A6E39C4584665A');
        $this->addSql('ALTER TABLE product_tag DROP CONSTRAINT FK_E3A6E39CBAD26311');
        $this->addSql('DROP TABLE brand_cascade');
        $this->addSql('DROP TABLE category_cascade');
        $this->addSql('DROP TABLE image_cascade');
        $this->addSql('DROP TABLE product_cascade');
        $this->addSql('DROP TABLE review_cascade');
        $this->addSql('DROP TABLE tag_cascade');
        $this->addSql('DROP TABLE variant_cascade');
    }
}
