<?php

declare(strict_types=1);

namespace Zenstruck\Foundry\Tests\Fixtures\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210820131819 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE bars (id INT AUTO_INCREMENT NOT NULL, foo_id INT DEFAULT NULL, INDEX IDX_2D7B7A718E48560F (foo_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE foos (id INT AUTO_INCREMENT NOT NULL, one_to_one_id INT NOT NULL, many_to_one_id INT NOT NULL, UNIQUE INDEX UNIQ_57EBAC30B549C760 (one_to_one_id), INDEX IDX_57EBAC30EAB5DEB (many_to_one_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE foo_bar (foo_id INT NOT NULL, bar_id INT NOT NULL, INDEX IDX_8D1AB1FE8E48560F (foo_id), INDEX IDX_8D1AB1FE89A253A (bar_id), PRIMARY KEY(foo_id, bar_id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE bars ADD CONSTRAINT FK_2D7B7A718E48560F FOREIGN KEY (foo_id) REFERENCES foos (id)');
        $this->addSql('ALTER TABLE foos ADD CONSTRAINT FK_57EBAC30B549C760 FOREIGN KEY (one_to_one_id) REFERENCES bars (id)');
        $this->addSql('ALTER TABLE foos ADD CONSTRAINT FK_57EBAC30EAB5DEB FOREIGN KEY (many_to_one_id) REFERENCES bars (id)');
        $this->addSql('ALTER TABLE foo_bar ADD CONSTRAINT FK_8D1AB1FE8E48560F FOREIGN KEY (foo_id) REFERENCES foos (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE foo_bar ADD CONSTRAINT FK_8D1AB1FE89A253A FOREIGN KEY (bar_id) REFERENCES bars (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE foos DROP FOREIGN KEY FK_57EBAC30B549C760');
        $this->addSql('ALTER TABLE foos DROP FOREIGN KEY FK_57EBAC30EAB5DEB');
        $this->addSql('ALTER TABLE foo_bar DROP FOREIGN KEY FK_8D1AB1FE89A253A');
        $this->addSql('ALTER TABLE bars DROP FOREIGN KEY FK_2D7B7A718E48560F');
        $this->addSql('ALTER TABLE foo_bar DROP FOREIGN KEY FK_8D1AB1FE8E48560F');
        $this->addSql('DROP TABLE bars');
        $this->addSql('DROP TABLE foos');
        $this->addSql('DROP TABLE foo_bar');
    }

    public function isTransactional(): bool
    {
        return false;
    }
}
