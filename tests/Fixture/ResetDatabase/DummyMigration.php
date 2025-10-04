<?php

namespace Zenstruck\Foundry\Tests\Fixture\ResetDatabase;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class DummyMigration extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Dummy migration to test migration reset with multiple configuration files.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('INSERT INTO address (city) VALUES (\'city\')');
    }
}
