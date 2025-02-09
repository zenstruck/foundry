<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Tests\Integration\Maker;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\MakerBundle\Exception\RuntimeCommandException;
use Symfony\Component\Console\Tester\CommandTester;
use Zenstruck\Foundry\Maker\Factory\FactoryGenerator;
use Zenstruck\Foundry\Tests\Fixture\Document\GenericDocument;
use Zenstruck\Foundry\Tests\Fixture\Document\WithEmbeddableDocument;
use Zenstruck\Foundry\Tests\Fixture\Entity\Category;
use Zenstruck\Foundry\Tests\Fixture\Entity\Contact;
use Zenstruck\Foundry\Tests\Fixture\Entity\GenericEntity;
use Zenstruck\Foundry\Tests\Fixture\Entity\WithEmbeddableEntity;
use Zenstruck\Foundry\Tests\Fixture\Object1;
use Zenstruck\Foundry\Tests\Fixture\ObjectWithEnum;
use Zenstruck\Foundry\Tests\Fixture\ObjectWithNonWriteable;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 * @group maker
 */
#[Group('maker')]
final class MakeFactoryTest extends MakerTestCase
{
    private const PHPSTAN_PATH = __DIR__.'/../../..'.FactoryGenerator::PHPSTAN_PATH;
    private const PSALM_PATH = __DIR__.'/../../..'.FactoryGenerator::PSALM_PATH;

    protected function setUp(): void
    {
        self::assertDirectoryDoesNotExist(self::tempDir());

        parent::setUp();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $removeSCAMock = static function(string $file): void {
            if (\file_exists($file)) {
                \unlink($file);
                \rmdir(\dirname($file));
                \rmdir(\dirname($file, 2));
            }
        };
        $removeSCAMock(self::PHPSTAN_PATH);
        $removeSCAMock(self::PSALM_PATH);
    }

    /**
     * @test
     */
    #[Test]
    public function can_create_factory(): void
    {
        if (!\getenv('DATABASE_URL')) {
            self::markTestSkipped('doctrine/orm not enabled.');
        }

        $tester = $this->makeFactoryCommandTester();

        $tester->execute(['class' => Category::class]);

        $output = $tester->getDisplay();

        $this->assertStringContainsString('Note: pass --test if you want to generate factories in your tests/ directory', $output);

        $this->assertFileFromMakerSameAsExpectedFile(self::tempFile('src/Factory/CategoryFactory.php'));
    }

    /**
     * @test
     */
    #[Test]
    public function can_create_factory_interactively(): void
    {
        if (!\getenv('DATABASE_URL')) {
            self::markTestSkipped('doctrine/orm not enabled.');
        }

        $tester = $this->makeFactoryCommandTester();

        $tester->setInputs([
            Contact::class, // which class to create a factory for?
            'yes', // should create PostFactory for Contact::$address?
        ]);
        $tester->execute([], ['interactive' => true]);

        $output = $tester->getDisplay();
        $this->assertStringContainsString(
            'A factory for class "Zenstruck\Foundry\Tests\Fixture\Entity\Address" is missing for field Contact::$address. Do you want to create it?',
            $output,
        );

        $this->assertFileExists(self::tempFile('src/Factory/AddressFactory.php'));
        $this->assertFileFromMakerSameAsExpectedFile(self::tempFile('src/Factory/ContactFactory.php'));
    }

    /**
     * @test
     */
    #[Test]
    public function can_create_factory_in_test_dir(): void
    {
        if (!\getenv('DATABASE_URL')) {
            self::markTestSkipped('doctrine/orm not enabled.');
        }

        $tester = $this->makeFactoryCommandTester();

        $tester->execute(['class' => Category::class, '--test' => true]);

        $this->assertFileExists(self::tempFile('tests/Factory/CategoryFactory.php'));
    }

    /**
     * @test
     * @dataProvider scaToolProvider
     */
    #[Test]
    #[DataProvider('scaToolProvider')]
    public function can_create_factory_with_static_analysis_annotations(string $scaTool): void
    {
        if (!\getenv('DATABASE_URL')) {
            self::markTestSkipped('doctrine/orm not enabled.');
        }

        $this->emulateSCAToolEnabled($scaTool);

        $tester = $this->makeFactoryCommandTester();

        $tester->execute(['class' => Category::class, '--test' => true, '--with-phpdoc' => true]);

        $this->assertFileFromMakerSameAsExpectedFile(self::tempFile('tests/Factory/CategoryFactory.php'));
    }

    /**
     * @return iterable<string, array{0: string}>
     */
    public static function scaToolProvider(): iterable
    {
        yield 'phpstan' => [self::PHPSTAN_PATH];
        yield 'psalm' => [self::PSALM_PATH];
    }

    /**
     * @test
     */
    #[Test]
    public function can_create_factory_for_entity_with_repository(): void
    {
        if (!\getenv('DATABASE_URL')) {
            self::markTestSkipped('doctrine/orm not enabled.');
        }

        $tester = $this->makeFactoryCommandTester();

        $tester->execute(['class' => GenericEntity::class, '--with-phpdoc' => true]);

        $this->assertFileFromMakerSameAsExpectedFile(self::tempFile('src/Factory/GenericEntityFactory.php'));
    }

    /**
     * @test
     */
    #[Test]
    public function invalid_entity_throws_exception(): void
    {
        $tester = $this->makeFactoryCommandTester();

        try {
            $tester->execute(['class' => 'Invalid']);
        } catch (RuntimeCommandException $e) {
            $this->assertSame('Class "Invalid" not found.', $e->getMessage());
            $this->assertFileDoesNotExist(self::tempFile('src/Factory/InvalidFactory.php'));

            return;
        }

        $this->fail('Exception not thrown.');
    }

    /**
     * @test
     */
    #[Test]
    public function can_create_factory_for_not_persisted_class(): void
    {
        $tester = $this->makeFactoryCommandTester();

        $tester->execute(['class' => Object1::class, '--no-persistence' => true, '--all-fields' => true]);

        $this->assertFileFromMakerSameAsExpectedFile(self::tempFile('src/Factory/Object1Factory.php'));
    }

    /**
     * @test
     */
    #[Test]
    public function can_create_factory_for_not_persisted_class_interactively(): void
    {
        $tester = $this->makeFactoryCommandTester();

        $tester->setInputs(['Foo', Object1::class]); // "Foo" will generate a validation error
        $tester->execute(['--no-persistence' => true]);

        $output = $tester->getDisplay();

        $this->assertStringContainsString('Not persisted fully qualified class name to create a factory for:', $output);
        $this->assertStringContainsString('[ERROR] Given class "Foo" does not exist', $output);

        $this->assertFileExists(self::tempFile('src/Factory/Object1Factory.php'));
    }

    /**
     * @test
     */
    #[Test]
    public function can_customize_namespace(): void
    {
        if (!\getenv('DATABASE_URL')) {
            self::markTestSkipped('doctrine/orm not enabled.');
        }

        $tester = $this->makeFactoryCommandTester();

        $tester->setInputs([GenericEntity::class]);
        $tester->execute(['--namespace' => 'My\\Namespace']);

        $expectedFile = self::tempFile('src/My/Namespace/GenericEntityFactory.php');
        $this->assertFileExists($expectedFile);
        $this->assertStringContainsString('namespace App\\My\\Namespace;', \file_get_contents($expectedFile) ?: '');
    }

    /**
     * @test
     */
    #[Test]
    public function can_customize_namespace_with_test_flag(): void
    {
        if (!\getenv('DATABASE_URL')) {
            self::markTestSkipped('doctrine/orm not enabled.');
        }

        $tester = $this->makeFactoryCommandTester();

        $tester->setInputs([GenericEntity::class]);
        $tester->execute(['--namespace' => 'My\\Namespace', '--test' => true]);

        $expectedFile = self::tempFile('tests/My/Namespace/GenericEntityFactory.php');
        $this->assertFileExists($expectedFile);
        $this->assertStringContainsString('namespace App\\Tests\\My\\Namespace;', \file_get_contents($expectedFile) ?: '');
    }

    /**
     * @test
     */
    #[Test]
    public function can_customize_namespace_with_root_namespace_prefix(): void
    {
        if (!\getenv('DATABASE_URL')) {
            self::markTestSkipped('doctrine/orm not enabled.');
        }

        $tester = $this->makeFactoryCommandTester();

        $tester->setInputs([GenericEntity::class]);
        $tester->execute(['--namespace' => 'App\\My\\Namespace']);

        $expectedFile = self::tempFile('src/My/Namespace/GenericEntityFactory.php');
        $this->assertFileExists($expectedFile);
        $this->assertStringContainsString('namespace App\\My\\Namespace;', \file_get_contents($expectedFile) ?: '');
    }

    /**
     * @test
     */
    #[Test]
    public function can_customize_namespace_with_test_flag_with_root_namespace_prefix(): void
    {
        if (!\getenv('DATABASE_URL')) {
            self::markTestSkipped('doctrine/orm not enabled.');
        }

        $tester = $this->makeFactoryCommandTester();

        $tester->setInputs([GenericEntity::class]);
        $tester->execute(['--namespace' => 'App\\Tests\\My\\Namespace', '--test' => true]);

        $expectedFile = self::tempFile('tests/My/Namespace/GenericEntityFactory.php');
        $this->assertFileExists($expectedFile);
        $this->assertStringContainsString('namespace App\\Tests\\My\\Namespace;', \file_get_contents($expectedFile) ?: '');
    }

    /**
     * @test
     * @dataProvider documentProvider
     */
    #[Test]
    #[DataProvider('documentProvider')]
    public function can_create_factory_for_odm(string $class, string $file): void
    {
        if (!\getenv('MONGO_URL')) {
            self::markTestSkipped('doctrine/odm not enabled.');
        }

        $tester = $this->makeFactoryCommandTester();

        $tester->setInputs([$class]);
        $tester->execute([]);

        $this->assertFileExists(self::tempFile("src/Factory/{$file}.php"));
    }

    /**
     * @return iterable<string, array{0: string, 1: string}>
     */
    public static function documentProvider(): iterable
    {
        yield 'document' => [GenericDocument::class, 'GenericDocumentFactory'];
        yield 'embedded document' => [WithEmbeddableDocument::class, 'WithEmbeddableDocumentFactory'];
    }

    /**
     * @test
     */
    #[Test]
    public function can_create_all_factories_for_doctrine_objects(): void
    {
        if (!\getenv('MONGO_URL') && !\getenv('DATABASE_URL')) {
            self::markTestSkipped('Some persistence should be activated.');
        }

        $tester = $this->makeFactoryCommandTester();

        $inputs = ['All']; // which factory to generate?

        $tester->setInputs($inputs);
        $tester->execute([]);

        $expectedFactories = [];

        if (\getenv('DATABASE_URL')) {
            $expectedFactories = ['EmbeddableFactory', 'GenericEntityFactory', 'GlobalEntityFactory', 'WithEmbeddableEntityFactory'];
        }

        if (\getenv('MONGO_URL')) {
            $expectedFactories = [...$expectedFactories, 'GenericDocumentFactory', 'GlobalDocumentFactory', 'WithEmbeddableDocumentFactory'];
        }

        self::assertGreaterThan(0, \count($expectedFactories));
        foreach ($expectedFactories as $expectedFactory) {
            $this->assertFileExists(self::tempFile("src/Factory/{$expectedFactory}.php"));
        }
    }

    /**
     * @test
     */
    #[Test]
    public function can_create_factory_with_auto_activated_not_persisted_option(): void
    {
        if (\getenv('MONGO_URL') || \getenv('DATABASE_URL')) {
            self::markTestSkipped('No persistence should be activated.');
        }

        $tester = $this->makeFactoryCommandTester();

        $tester->execute(['class' => GenericEntity::class]);

        $output = $tester->getDisplay();
        $this->assertStringContainsString('Note: Doctrine not enabled: auto-activating --no-persistence option.', $output);

        $this->assertFileFromMakerSameAsExpectedFile(self::tempFile('src/Factory/GenericEntityFactory.php'));
    }

    /**
     * @test
     */
    #[Test]
    public function can_create_factory_with_all_fields(): void
    {
        if (!\getenv('DATABASE_URL')) {
            self::markTestSkipped('doctrine/orm not enabled.');
        }

        $tester = $this->makeFactoryCommandTester();

        $tester->execute(['class' => GenericEntity::class, '--all-fields' => true]);

        $this->assertFileFromMakerSameAsExpectedFile(self::tempFile('src/Factory/GenericEntityFactory.php'));
    }

    /**
     * @test
     * @dataProvider objectsWithEmbeddableProvider
     */
    #[Test]
    #[DataProvider('objectsWithEmbeddableProvider')]
    public function can_create_factory_with_embeddable(string $objectClass, string $objectFactoryName): void
    {
        $tester = $this->makeFactoryCommandTester();

        $tester->execute(['class' => $objectClass, '--all-fields' => true]);

        $this->assertFileExists(self::tempFile('src/Factory/EmbeddableFactory.php'));
        $this->assertFileFromMakerSameAsExpectedFile(self::tempFile("src/Factory/{$objectFactoryName}.php"));
    }

    /**
     * @return iterable<string, array{0: string, 1: string}>
     */
    public static function objectsWithEmbeddableProvider(): iterable
    {
        if (\getenv('DATABASE_URL')) {
            yield 'orm' => [WithEmbeddableEntity::class, 'WithEmbeddableEntityFactory'];
        }

        if (\getenv('MONGO_URL')) {
            yield 'odm' => [WithEmbeddableDocument::class, 'WithEmbeddableDocumentFactory'];
        }
    }

    /**
     * @test
     */
    #[Test]
    public function can_create_factory_with_default_enum(): void
    {
        $tester = $this->makeFactoryCommandTester();

        $tester->execute(['class' => ObjectWithEnum::class, '--no-persistence' => true]);

        $this->assertFileFromMakerSameAsExpectedFile(self::tempFile('src/Factory/ObjectWithEnumFactory.php'));
    }

    /**
     * @test
     */
    #[Test]
    public function does_not_initialize_non_settable(): void
    {
        $tester = $this->makeFactoryCommandTester();

        $tester->execute(['class' => ObjectWithNonWriteable::class, '--no-persistence' => true]);

        $this->assertFileFromMakerSameAsExpectedFile(self::tempFile('src/Factory/ObjectWithNonWriteableFactory.php'));
    }

    /**
     * @test
     */
    #[Test]
    public function does_force_initialization_of_non_settable_with_always_force(): void
    {
        $tester = $this->makeFactoryCommandTester(['environment' => 'always_force']);

        $tester->execute(['class' => ObjectWithNonWriteable::class, '--no-persistence' => true]);

        $this->assertFileFromMakerSameAsExpectedFile(self::tempFile('src/Factory/ObjectWithNonWriteableFactory.php'));
    }

    private function emulateSCAToolEnabled(string $scaToolFilePath): void
    {
        \mkdir(\dirname($scaToolFilePath), 0777, true);
        \touch($scaToolFilePath);
    }

    private function makeFactoryCommandTester(array $options = []): CommandTester
    {
        return new CommandTester((new Application(self::bootKernel($options)))->find('make:factory'));
    }
}
