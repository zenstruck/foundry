<?php

declare(strict_types=1);

namespace Zenstruck\Foundry\Tests\Fixtures\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20221204165430 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Second migration.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE brand_cascade (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE category_cascade (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE productcategory_product (productcategory_id INT NOT NULL, product_id INT NOT NULL, INDEX IDX_5BC2A6A2E26A32B1 (productcategory_id), INDEX IDX_5BC2A6A24584665A (product_id), PRIMARY KEY(productcategory_id, product_id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE image_cascade (id INT AUTO_INCREMENT NOT NULL, path VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE product_cascade (id INT AUTO_INCREMENT NOT NULL, brand_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, INDEX IDX_D7FE16D844F5D008 (brand_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE product_producttag (product_id INT NOT NULL, producttag_id INT NOT NULL, INDEX IDX_B32B4BC24584665A (product_id), INDEX IDX_B32B4BC291B6F4D1 (producttag_id), PRIMARY KEY(product_id, producttag_id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE review_cascade (id INT AUTO_INCREMENT NOT NULL, product_id INT DEFAULT NULL, ranking INT NOT NULL, UNIQUE INDEX UNIQ_9DC9B99F4584665A (product_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tag_cascade (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE variant_cascade (id INT AUTO_INCREMENT NOT NULL, product_id INT DEFAULT NULL, image_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, INDEX IDX_6982202E4584665A (product_id), UNIQUE INDEX UNIQ_6982202E3DA5256D (image_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE productcategory_product ADD CONSTRAINT FK_5BC2A6A2E26A32B1 FOREIGN KEY (productcategory_id) REFERENCES category_cascade (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE productcategory_product ADD CONSTRAINT FK_5BC2A6A24584665A FOREIGN KEY (product_id) REFERENCES product_cascade (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE entity_with_relations ADD CONSTRAINT FK_A9C9EC96FF92FDCA FOREIGN KEY (manyToOneWithNotExistingFactory_id) REFERENCES brand_cascade (id)');
        $this->addSql('ALTER TABLE product_cascade ADD CONSTRAINT FK_D7FE16D844F5D008 FOREIGN KEY (brand_id) REFERENCES brand_cascade (id)');
        $this->addSql('ALTER TABLE product_producttag ADD CONSTRAINT FK_B32B4BC24584665A FOREIGN KEY (product_id) REFERENCES product_cascade (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE product_producttag ADD CONSTRAINT FK_B32B4BC291B6F4D1 FOREIGN KEY (producttag_id) REFERENCES tag_cascade (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE review_cascade ADD CONSTRAINT FK_9DC9B99F4584665A FOREIGN KEY (product_id) REFERENCES product_cascade (id)');
        $this->addSql('ALTER TABLE variant_cascade ADD CONSTRAINT FK_6982202E4584665A FOREIGN KEY (product_id) REFERENCES product_cascade (id)');
        $this->addSql('ALTER TABLE variant_cascade ADD CONSTRAINT FK_6982202E3DA5256D FOREIGN KEY (image_id) REFERENCES image_cascade (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE entity_with_relations DROP FOREIGN KEY FK_A9C9EC96FF92FDCA');
        $this->addSql('ALTER TABLE productcategory_product DROP FOREIGN KEY FK_5BC2A6A2E26A32B1');
        $this->addSql('ALTER TABLE productcategory_product DROP FOREIGN KEY FK_5BC2A6A24584665A');
        $this->addSql('ALTER TABLE product_cascade DROP FOREIGN KEY FK_D7FE16D844F5D008');
        $this->addSql('ALTER TABLE product_producttag DROP FOREIGN KEY FK_B32B4BC24584665A');
        $this->addSql('ALTER TABLE product_producttag DROP FOREIGN KEY FK_B32B4BC291B6F4D1');
        $this->addSql('ALTER TABLE review_cascade DROP FOREIGN KEY FK_9DC9B99F4584665A');
        $this->addSql('ALTER TABLE variant_cascade DROP FOREIGN KEY FK_6982202E4584665A');
        $this->addSql('ALTER TABLE variant_cascade DROP FOREIGN KEY FK_6982202E3DA5256D');
        $this->addSql('DROP TABLE brand_cascade');
        $this->addSql('DROP TABLE category_cascade');
        $this->addSql('DROP TABLE productcategory_product');
        $this->addSql('DROP TABLE image_cascade');
        $this->addSql('DROP TABLE product_cascade');
        $this->addSql('DROP TABLE product_producttag');
        $this->addSql('DROP TABLE review_cascade');
        $this->addSql('DROP TABLE tag_cascade');
        $this->addSql('DROP TABLE variant_cascade');
    }

    public function isTransactional(): bool
    {
        return false;
    }
}
