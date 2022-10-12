<?php

declare(strict_types=1);

namespace Zenstruck\Foundry\Tests\Fixtures\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230109210404 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE CascadeRichComment (id INT AUTO_INCREMENT NOT NULL, post_id INT NOT NULL, body LONGTEXT NOT NULL, INDEX IDX_A4B2EA424B89032C (post_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE CascadeRichPost (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE RichComment (id INT AUTO_INCREMENT NOT NULL, post_id INT NOT NULL, body LONGTEXT NOT NULL, INDEX IDX_C6E248524B89032C (post_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE RichPost (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE CascadeRichComment ADD CONSTRAINT FK_A4B2EA424B89032C FOREIGN KEY (post_id) REFERENCES CascadeRichPost (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE RichComment ADD CONSTRAINT FK_C6E248524B89032C FOREIGN KEY (post_id) REFERENCES RichPost (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE CascadeRichComment DROP FOREIGN KEY FK_A4B2EA424B89032C');
        $this->addSql('ALTER TABLE RichComment DROP FOREIGN KEY FK_C6E248524B89032C');
        $this->addSql('DROP TABLE CascadeRichComment');
        $this->addSql('DROP TABLE CascadeRichPost');
        $this->addSql('DROP TABLE RichComment');
        $this->addSql('DROP TABLE RichPost');
    }
}
