<?php

declare(strict_types=1);

namespace Zenstruck\Foundry\Tests\Fixtures\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230513160345 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'first migration';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE SEQUENCE categories_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE comments_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE contacts_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE entity_for_relations_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE entity_with_relations_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE posts_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE tags_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE users_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE categories (id INT NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE productcategory_product (productcategory_id INT NOT NULL, product_id INT NOT NULL, PRIMARY KEY(productcategory_id, product_id))');
        $this->addSql('CREATE INDEX IDX_5BC2A6A2E26A32B1 ON productcategory_product (productcategory_id)');
        $this->addSql('CREATE INDEX IDX_5BC2A6A24584665A ON productcategory_product (product_id)');
        $this->addSql('CREATE TABLE comments (id INT NOT NULL, user_id INT NOT NULL, post_id INT NOT NULL, body TEXT NOT NULL, createdAt TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, approved BOOLEAN NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_5F9E962AA76ED395 ON comments (user_id)');
        $this->addSql('CREATE INDEX IDX_5F9E962A4B89032C ON comments (post_id)');
        $this->addSql('CREATE TABLE contacts (id INT NOT NULL, name VARCHAR(255) NOT NULL, address_value VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE entity_for_relations (id INT NOT NULL, manyToOne_id INT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_C63B81552E3A088A ON entity_for_relations (manyToOne_id)');
        $this->addSql('CREATE TABLE entity_with_relations (id INT NOT NULL, oneToOne_id INT NOT NULL, oneToOneNullable_id INT DEFAULT NULL, manyToOne_id INT NOT NULL, manyToOneNullable_id INT DEFAULT NULL, manyToOneNullableDefault_id INT DEFAULT NULL, manyToOneWithNotExistingFactory_id INT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_A9C9EC969017888C ON entity_with_relations (oneToOne_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_A9C9EC96DA2BFB84 ON entity_with_relations (oneToOneNullable_id)');
        $this->addSql('CREATE INDEX IDX_A9C9EC962E3A088A ON entity_with_relations (manyToOne_id)');
        $this->addSql('CREATE INDEX IDX_A9C9EC968097B86C ON entity_with_relations (manyToOneNullable_id)');
        $this->addSql('CREATE INDEX IDX_A9C9EC968572C13C ON entity_with_relations (manyToOneNullableDefault_id)');
        $this->addSql('CREATE INDEX IDX_A9C9EC96FF92FDCA ON entity_with_relations (manyToOneWithNotExistingFactory_id)');
        $this->addSql('CREATE TABLE entitywithrelations_category (entitywithrelations_id INT NOT NULL, category_id INT NOT NULL, PRIMARY KEY(entitywithrelations_id, category_id))');
        $this->addSql('CREATE INDEX IDX_CD6EBFAB337AA4F7 ON entitywithrelations_category (entitywithrelations_id)');
        $this->addSql('CREATE INDEX IDX_CD6EBFAB12469DE2 ON entitywithrelations_category (category_id)');
        $this->addSql('CREATE TABLE posts (id INT NOT NULL, category_id INT DEFAULT NULL, secondary_category_id INT DEFAULT NULL, title VARCHAR(255) NOT NULL, body TEXT NOT NULL, shortDescription VARCHAR(255) DEFAULT NULL, viewCount INT NOT NULL, createdAt TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, publishedAt TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, mostRelevantRelatedPost_id INT DEFAULT NULL, lessRelevantRelatedPost_id INT DEFAULT NULL, type VARCHAR(255) NOT NULL, specificProperty VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_885DBAFA12469DE2 ON posts (category_id)');
        $this->addSql('CREATE INDEX IDX_885DBAFAEA0D7566 ON posts (secondary_category_id)');
        $this->addSql('CREATE INDEX IDX_885DBAFAD126F51 ON posts (mostRelevantRelatedPost_id)');
        $this->addSql('CREATE INDEX IDX_885DBAFA20DBE482 ON posts (lessRelevantRelatedPost_id)');
        $this->addSql('CREATE TABLE post_tag (post_id INT NOT NULL, tag_id INT NOT NULL, PRIMARY KEY(post_id, tag_id))');
        $this->addSql('CREATE INDEX IDX_5ACE3AF04B89032C ON post_tag (post_id)');
        $this->addSql('CREATE INDEX IDX_5ACE3AF0BAD26311 ON post_tag (tag_id)');
        $this->addSql('CREATE TABLE post_tag_secondary (post_id INT NOT NULL, tag_id INT NOT NULL, PRIMARY KEY(post_id, tag_id))');
        $this->addSql('CREATE INDEX IDX_1515F0214B89032C ON post_tag_secondary (post_id)');
        $this->addSql('CREATE INDEX IDX_1515F021BAD26311 ON post_tag_secondary (tag_id)');
        $this->addSql('CREATE TABLE post_post (post_source INT NOT NULL, post_target INT NOT NULL, PRIMARY KEY(post_source, post_target))');
        $this->addSql('CREATE INDEX IDX_93DF0B866FA89B16 ON post_post (post_source)');
        $this->addSql('CREATE INDEX IDX_93DF0B86764DCB99 ON post_post (post_target)');
        $this->addSql('CREATE TABLE product_tag (product_id INT NOT NULL, tag_id INT NOT NULL, PRIMARY KEY(product_id, tag_id))');
        $this->addSql('CREATE INDEX IDX_E3A6E39C4584665A ON product_tag (product_id)');
        $this->addSql('CREATE INDEX IDX_E3A6E39CBAD26311 ON product_tag (tag_id)');
        $this->addSql('CREATE TABLE tags (id INT NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE users (id INT NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('ALTER TABLE comments ADD CONSTRAINT FK_5F9E962AA76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE comments ADD CONSTRAINT FK_5F9E962A4B89032C FOREIGN KEY (post_id) REFERENCES posts (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE entity_for_relations ADD CONSTRAINT FK_C63B81552E3A088A FOREIGN KEY (manyToOne_id) REFERENCES entity_with_relations (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE entity_with_relations ADD CONSTRAINT FK_A9C9EC969017888C FOREIGN KEY (oneToOne_id) REFERENCES categories (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE entity_with_relations ADD CONSTRAINT FK_A9C9EC96DA2BFB84 FOREIGN KEY (oneToOneNullable_id) REFERENCES categories (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE entity_with_relations ADD CONSTRAINT FK_A9C9EC962E3A088A FOREIGN KEY (manyToOne_id) REFERENCES categories (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE entity_with_relations ADD CONSTRAINT FK_A9C9EC968097B86C FOREIGN KEY (manyToOneNullable_id) REFERENCES categories (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE entity_with_relations ADD CONSTRAINT FK_A9C9EC968572C13C FOREIGN KEY (manyToOneNullableDefault_id) REFERENCES categories (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE entitywithrelations_category ADD CONSTRAINT FK_CD6EBFAB337AA4F7 FOREIGN KEY (entitywithrelations_id) REFERENCES entity_with_relations (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE entitywithrelations_category ADD CONSTRAINT FK_CD6EBFAB12469DE2 FOREIGN KEY (category_id) REFERENCES categories (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE posts ADD CONSTRAINT FK_885DBAFA12469DE2 FOREIGN KEY (category_id) REFERENCES categories (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE posts ADD CONSTRAINT FK_885DBAFAEA0D7566 FOREIGN KEY (secondary_category_id) REFERENCES categories (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE posts ADD CONSTRAINT FK_885DBAFAD126F51 FOREIGN KEY (mostRelevantRelatedPost_id) REFERENCES posts (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE posts ADD CONSTRAINT FK_885DBAFA20DBE482 FOREIGN KEY (lessRelevantRelatedPost_id) REFERENCES posts (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE post_tag ADD CONSTRAINT FK_5ACE3AF04B89032C FOREIGN KEY (post_id) REFERENCES posts (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE post_tag ADD CONSTRAINT FK_5ACE3AF0BAD26311 FOREIGN KEY (tag_id) REFERENCES tags (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE post_tag_secondary ADD CONSTRAINT FK_1515F0214B89032C FOREIGN KEY (post_id) REFERENCES posts (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE post_tag_secondary ADD CONSTRAINT FK_1515F021BAD26311 FOREIGN KEY (tag_id) REFERENCES tags (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE post_post ADD CONSTRAINT FK_93DF0B866FA89B16 FOREIGN KEY (post_source) REFERENCES posts (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE post_post ADD CONSTRAINT FK_93DF0B86764DCB99 FOREIGN KEY (post_target) REFERENCES posts (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP SEQUENCE categories_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE comments_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE contacts_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE entity_for_relations_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE entity_with_relations_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE posts_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE tags_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE users_id_seq CASCADE');
        $this->addSql('ALTER TABLE productcategory_product DROP CONSTRAINT FK_5BC2A6A2E26A32B1');
        $this->addSql('ALTER TABLE productcategory_product DROP CONSTRAINT FK_5BC2A6A24584665A');
        $this->addSql('ALTER TABLE comments DROP CONSTRAINT FK_5F9E962AA76ED395');
        $this->addSql('ALTER TABLE comments DROP CONSTRAINT FK_5F9E962A4B89032C');
        $this->addSql('ALTER TABLE entity_for_relations DROP CONSTRAINT FK_C63B81552E3A088A');
        $this->addSql('ALTER TABLE entity_with_relations DROP CONSTRAINT FK_A9C9EC969017888C');
        $this->addSql('ALTER TABLE entity_with_relations DROP CONSTRAINT FK_A9C9EC96DA2BFB84');
        $this->addSql('ALTER TABLE entity_with_relations DROP CONSTRAINT FK_A9C9EC962E3A088A');
        $this->addSql('ALTER TABLE entity_with_relations DROP CONSTRAINT FK_A9C9EC968097B86C');
        $this->addSql('ALTER TABLE entity_with_relations DROP CONSTRAINT FK_A9C9EC968572C13C');
        $this->addSql('ALTER TABLE entity_with_relations DROP CONSTRAINT FK_A9C9EC96FF92FDCA');
        $this->addSql('ALTER TABLE entitywithrelations_category DROP CONSTRAINT FK_CD6EBFAB337AA4F7');
        $this->addSql('ALTER TABLE entitywithrelations_category DROP CONSTRAINT FK_CD6EBFAB12469DE2');
        $this->addSql('ALTER TABLE posts DROP CONSTRAINT FK_885DBAFA12469DE2');
        $this->addSql('ALTER TABLE posts DROP CONSTRAINT FK_885DBAFAEA0D7566');
        $this->addSql('ALTER TABLE posts DROP CONSTRAINT FK_885DBAFAD126F51');
        $this->addSql('ALTER TABLE posts DROP CONSTRAINT FK_885DBAFA20DBE482');
        $this->addSql('ALTER TABLE post_tag DROP CONSTRAINT FK_5ACE3AF04B89032C');
        $this->addSql('ALTER TABLE post_tag DROP CONSTRAINT FK_5ACE3AF0BAD26311');
        $this->addSql('ALTER TABLE post_tag_secondary DROP CONSTRAINT FK_1515F0214B89032C');
        $this->addSql('ALTER TABLE post_tag_secondary DROP CONSTRAINT FK_1515F021BAD26311');
        $this->addSql('ALTER TABLE post_post DROP CONSTRAINT FK_93DF0B866FA89B16');
        $this->addSql('ALTER TABLE post_post DROP CONSTRAINT FK_93DF0B86764DCB99');
        $this->addSql('ALTER TABLE product_tag DROP CONSTRAINT FK_E3A6E39C4584665A');
        $this->addSql('ALTER TABLE product_tag DROP CONSTRAINT FK_E3A6E39CBAD26311');
        $this->addSql('DROP TABLE categories');
        $this->addSql('DROP TABLE productcategory_product');
        $this->addSql('DROP TABLE comments');
        $this->addSql('DROP TABLE contacts');
        $this->addSql('DROP TABLE entity_for_relations');
        $this->addSql('DROP TABLE entity_with_relations');
        $this->addSql('DROP TABLE entitywithrelations_category');
        $this->addSql('DROP TABLE posts');
        $this->addSql('DROP TABLE post_tag');
        $this->addSql('DROP TABLE post_tag_secondary');
        $this->addSql('DROP TABLE post_post');
        $this->addSql('DROP TABLE product_tag');
        $this->addSql('DROP TABLE tags');
        $this->addSql('DROP TABLE users');
    }
}
