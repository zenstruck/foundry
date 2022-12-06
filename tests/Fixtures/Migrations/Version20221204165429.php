<?php

declare(strict_types=1);

namespace Zenstruck\Foundry\Tests\Fixtures\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20221204165429 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'First migration';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE categories (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE comments (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, post_id INT NOT NULL, body LONGTEXT NOT NULL, createdAt DATETIME NOT NULL, approved TINYINT(1) NOT NULL, INDEX IDX_5F9E962AA76ED395 (user_id), INDEX IDX_5F9E962A4B89032C (post_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE contacts (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, address_value VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE entity_for_relations (id INT AUTO_INCREMENT NOT NULL, manyToOne_id INT DEFAULT NULL, INDEX IDX_C63B81552E3A088A (manyToOne_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE entity_with_relations (id INT AUTO_INCREMENT NOT NULL, oneToOne_id INT NOT NULL, oneToOneNullable_id INT DEFAULT NULL, manyToOne_id INT NOT NULL, manyToOneNullable_id INT DEFAULT NULL, manyToOneNullableDefault_id INT DEFAULT NULL, manyToOneWithNotExistingFactory_id INT NOT NULL, UNIQUE INDEX UNIQ_A9C9EC969017888C (oneToOne_id), UNIQUE INDEX UNIQ_A9C9EC96DA2BFB84 (oneToOneNullable_id), INDEX IDX_A9C9EC962E3A088A (manyToOne_id), INDEX IDX_A9C9EC968097B86C (manyToOneNullable_id), INDEX IDX_A9C9EC968572C13C (manyToOneNullableDefault_id), INDEX IDX_A9C9EC96FF92FDCA (manyToOneWithNotExistingFactory_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE entitywithrelations_category (entitywithrelations_id INT NOT NULL, category_id INT NOT NULL, INDEX IDX_CD6EBFAB337AA4F7 (entitywithrelations_id), INDEX IDX_CD6EBFAB12469DE2 (category_id), PRIMARY KEY(entitywithrelations_id, category_id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE posts (id INT AUTO_INCREMENT NOT NULL, category_id INT DEFAULT NULL, secondary_category_id INT DEFAULT NULL, title VARCHAR(255) NOT NULL, body LONGTEXT NOT NULL, shortDescription VARCHAR(255) DEFAULT NULL, viewCount INT NOT NULL, createdAt DATETIME NOT NULL, publishedAt DATETIME DEFAULT NULL, mostRelevantRelatedPost_id INT DEFAULT NULL, lessRelevantRelatedPost_id INT DEFAULT NULL, type VARCHAR(255) NOT NULL, specificProperty VARCHAR(255) DEFAULT NULL, INDEX IDX_885DBAFA12469DE2 (category_id), INDEX IDX_885DBAFAEA0D7566 (secondary_category_id), INDEX IDX_885DBAFAD126F51 (mostRelevantRelatedPost_id), INDEX IDX_885DBAFA20DBE482 (lessRelevantRelatedPost_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE post_tag (post_id INT NOT NULL, tag_id INT NOT NULL, INDEX IDX_5ACE3AF04B89032C (post_id), INDEX IDX_5ACE3AF0BAD26311 (tag_id), PRIMARY KEY(post_id, tag_id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE post_tag_secondary (post_id INT NOT NULL, tag_id INT NOT NULL, INDEX IDX_1515F0214B89032C (post_id), INDEX IDX_1515F021BAD26311 (tag_id), PRIMARY KEY(post_id, tag_id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE post_post (post_source INT NOT NULL, post_target INT NOT NULL, INDEX IDX_93DF0B866FA89B16 (post_source), INDEX IDX_93DF0B86764DCB99 (post_target), PRIMARY KEY(post_source, post_target)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tags (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE users (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE comments ADD CONSTRAINT FK_5F9E962AA76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE comments ADD CONSTRAINT FK_5F9E962A4B89032C FOREIGN KEY (post_id) REFERENCES posts (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE entity_for_relations ADD CONSTRAINT FK_C63B81552E3A088A FOREIGN KEY (manyToOne_id) REFERENCES entity_with_relations (id)');
        $this->addSql('ALTER TABLE entity_with_relations ADD CONSTRAINT FK_A9C9EC969017888C FOREIGN KEY (oneToOne_id) REFERENCES categories (id)');
        $this->addSql('ALTER TABLE entity_with_relations ADD CONSTRAINT FK_A9C9EC96DA2BFB84 FOREIGN KEY (oneToOneNullable_id) REFERENCES categories (id)');
        $this->addSql('ALTER TABLE entity_with_relations ADD CONSTRAINT FK_A9C9EC962E3A088A FOREIGN KEY (manyToOne_id) REFERENCES categories (id)');
        $this->addSql('ALTER TABLE entity_with_relations ADD CONSTRAINT FK_A9C9EC968097B86C FOREIGN KEY (manyToOneNullable_id) REFERENCES categories (id)');
        $this->addSql('ALTER TABLE entity_with_relations ADD CONSTRAINT FK_A9C9EC968572C13C FOREIGN KEY (manyToOneNullableDefault_id) REFERENCES categories (id)');
        $this->addSql('ALTER TABLE entitywithrelations_category ADD CONSTRAINT FK_CD6EBFAB337AA4F7 FOREIGN KEY (entitywithrelations_id) REFERENCES entity_with_relations (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE entitywithrelations_category ADD CONSTRAINT FK_CD6EBFAB12469DE2 FOREIGN KEY (category_id) REFERENCES categories (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE posts ADD CONSTRAINT FK_885DBAFA12469DE2 FOREIGN KEY (category_id) REFERENCES categories (id)');
        $this->addSql('ALTER TABLE posts ADD CONSTRAINT FK_885DBAFAEA0D7566 FOREIGN KEY (secondary_category_id) REFERENCES categories (id)');
        $this->addSql('ALTER TABLE posts ADD CONSTRAINT FK_885DBAFAD126F51 FOREIGN KEY (mostRelevantRelatedPost_id) REFERENCES posts (id)');
        $this->addSql('ALTER TABLE posts ADD CONSTRAINT FK_885DBAFA20DBE482 FOREIGN KEY (lessRelevantRelatedPost_id) REFERENCES posts (id)');
        $this->addSql('ALTER TABLE post_tag ADD CONSTRAINT FK_5ACE3AF04B89032C FOREIGN KEY (post_id) REFERENCES posts (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE post_tag ADD CONSTRAINT FK_5ACE3AF0BAD26311 FOREIGN KEY (tag_id) REFERENCES tags (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE post_tag_secondary ADD CONSTRAINT FK_1515F0214B89032C FOREIGN KEY (post_id) REFERENCES posts (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE post_tag_secondary ADD CONSTRAINT FK_1515F021BAD26311 FOREIGN KEY (tag_id) REFERENCES tags (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE post_post ADD CONSTRAINT FK_93DF0B866FA89B16 FOREIGN KEY (post_source) REFERENCES posts (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE post_post ADD CONSTRAINT FK_93DF0B86764DCB99 FOREIGN KEY (post_target) REFERENCES posts (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
    }

    public function isTransactional(): bool
    {
        return false;
    }
}
