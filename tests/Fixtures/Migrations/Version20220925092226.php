<?php

declare(strict_types=1);

namespace Zenstruck\Foundry\Tests\Fixtures\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220925092226 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE posts ADD mostRelevantRelatedPost_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE posts ADD CONSTRAINT FK_885DBAFAD126F51 FOREIGN KEY (mostRelevantRelatedPost_id) REFERENCES posts (id)');
        $this->addSql('CREATE INDEX IDX_885DBAFAD126F51 ON posts (mostRelevantRelatedPost_id)');
        $this->addSql('ALTER TABLE posts ADD lessRelevantRelatedPost_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE posts ADD CONSTRAINT FK_985DBAFAD126F52 FOREIGN KEY (lessRelevantRelatedPost_id) REFERENCES posts (id)');
        $this->addSql('CREATE INDEX IDX_985DBAFAD126F52 ON posts (lessRelevantRelatedPost_id)');

        $this->addSql('CREATE TABLE post_tag_secondary (post_id INT NOT NULL, tag_id INT NOT NULL, INDEX IDX_1515F0214B89032C (post_id), INDEX IDX_1515F021BAD26311 (tag_id), PRIMARY KEY(post_id, tag_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE post_tag_secondary ADD CONSTRAINT FK_1515F0214B89032C FOREIGN KEY (post_id) REFERENCES posts (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE post_tag_secondary ADD CONSTRAINT FK_1515F021BAD26311 FOREIGN KEY (tag_id) REFERENCES tags (id) ON DELETE CASCADE');

        $this->addSql('ALTER TABLE posts ADD secondary_category_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE posts ADD CONSTRAINT FK_885DBAFAEA0D7566 FOREIGN KEY (secondary_category_id) REFERENCES categories (id)');
        $this->addSql('CREATE INDEX IDX_885DBAFAEA0D7566 ON posts (secondary_category_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE posts DROP FOREIGN KEY FK_985DBAFAD126F52');
        $this->addSql('DROP INDEX IDX_985DBAFAD126F52 ON posts');
        $this->addSql('ALTER TABLE posts DROP lessRelevantRelatedPost_id');
        $this->addSql('ALTER TABLE posts DROP FOREIGN KEY FK_885DBAFAD126F51');
        $this->addSql('DROP INDEX IDX_885DBAFAD126F51 ON posts');
        $this->addSql('ALTER TABLE posts DROP mostRelevantRelatedPost_id');

        $this->addSql('ALTER TABLE post_tag_secondary DROP FOREIGN KEY FK_1515F0214B89032C');
        $this->addSql('ALTER TABLE post_tag_secondary DROP FOREIGN KEY FK_1515F021BAD26311');
        $this->addSql('DROP TABLE post_tag_secondary');

        $this->addSql('ALTER TABLE posts DROP FOREIGN KEY FK_885DBAFAEA0D7566');
        $this->addSql('DROP INDEX IDX_885DBAFAEA0D7566 ON posts');
        $this->addSql('ALTER TABLE posts DROP secondary_category_id');
    }
}
