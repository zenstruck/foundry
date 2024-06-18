<?php

declare(strict_types=1);

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

// to "Migrations" directory on boot (cf. bootstrap.php)

namespace Zenstruck\Foundry\Tests\Fixture\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Zenstruck\Foundry\Tests\Fixture\EdgeCases\Migrate\ORM\EntityInAnotherSchema\Article;

/**
 * Create custom "cms" schema ({@see Article}) to ensure "migrate" mode is still working with multiple schemas.
 * Note: the doctrine:migrations:diff command doesn't seem able to add this custom "CREATE SCHEMA" automatically.
 *
 * @see https://github.com/zenstruck/foundry/issues/618
 */
final class Version20240611065130 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create custom "cms" schema.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE SCHEMA cms');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP SCHEMA cms');
    }
}
