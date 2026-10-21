<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Tests\Unit\ORM\ResetDatabase;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\DBAL\DriverManager;
use Doctrine\Migrations\Configuration\Connection\ExistingConnection;
use Doctrine\Migrations\Configuration\Migration\ConfigurationArray;
use Doctrine\Migrations\DependencyFactory;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Zenstruck\Foundry\ORM\ResetDatabase\AutoDatabaseResetter;
use Zenstruck\Foundry\ORM\ResetDatabase\MigrateDatabaseResetter;
use Zenstruck\Foundry\ORM\ResetDatabase\OrmResetter;
use Zenstruck\Foundry\ORM\ResetDatabase\SchemaDatabaseResetter;

final class AutoDatabaseResetterTest extends TestCase
{
    private string $migrationsDirectory;

    protected function setUp(): void
    {
        $this->migrationsDirectory = \sys_get_temp_dir().'/foundry-auto-resetter-migrations';

        if (!\class_exists(DependencyFactory::class) || !\class_exists(Registry::class)) {
            self::markTestSkipped('doctrine/migrations and doctrine/doctrine-bundle are needed.');
        }

        (new Filesystem())->mkdir($this->migrationsDirectory);
    }

    protected function tearDown(): void
    {
        (new Filesystem())->remove($this->migrationsDirectory);
    }

    /**
     * @test
     */
    #[Test]
    public function it_resets_the_schema_without_the_migrations_bundle(): void
    {
        self::assertInstanceOf(SchemaDatabaseResetter::class, $this->innerResetter(null));
    }

    /**
     * @test
     */
    #[Test]
    public function it_resets_the_schema_when_there_are_no_migrations(): void
    {
        self::assertInstanceOf(SchemaDatabaseResetter::class, $this->innerResetter($this->dependencyFactory()));
    }

    /**
     * @test
     */
    #[Test]
    public function it_migrates_when_there_are_migrations(): void
    {
        \file_put_contents($this->migrationsDirectory.'/Version1.php', <<<'PHP'
            <?php

            namespace Zenstruck\Foundry\Tests\Unit\ORM\ResetDatabase\Migrations;

            use Doctrine\DBAL\Schema\Schema;
            use Doctrine\Migrations\AbstractMigration;

            final class Version1 extends AbstractMigration
            {
                public function up(Schema $schema): void
                {
                }
            }
            PHP);

        self::assertInstanceOf(MigrateDatabaseResetter::class, $this->innerResetter($this->dependencyFactory()));
    }

    private function dependencyFactory(): DependencyFactory
    {
        return DependencyFactory::fromConnection(
            new ConfigurationArray(['migrations_paths' => ['Zenstruck\Foundry\Tests\Unit\ORM\ResetDatabase\Migrations' => $this->migrationsDirectory]]),
            new ExistingConnection(DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true])),
        );
    }

    private function innerResetter(?DependencyFactory $dependencyFactory): OrmResetter
    {
        $resetter = new AutoDatabaseResetter($this->createStub(Registry::class), ['default'], ['default'], $dependencyFactory);

        // the resolved resetter is private: resolving it through a reset would require a booted kernel and a database
        return (new \ReflectionMethod($resetter, 'inner'))->invoke($resetter);
    }
}
