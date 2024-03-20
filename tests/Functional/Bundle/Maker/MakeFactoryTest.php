<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Tests\Functional\Bundle\Maker;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\MakerBundle\Exception\RuntimeCommandException;
use Symfony\Bundle\MakerBundle\Str;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\HttpKernel\KernelInterface;
use Zenstruck\Foundry\Bundle\Maker\Factory\FactoryGenerator;
use Zenstruck\Foundry\Tests\Fixtures\Document\ODMComment;
use Zenstruck\Foundry\Tests\Fixtures\Document\ODMPost;
use Zenstruck\Foundry\Tests\Fixtures\Document\Tag as AnotherTagClass;
use Zenstruck\Foundry\Tests\Fixtures\Entity\Address;
use Zenstruck\Foundry\Tests\Fixtures\Entity\Cascade\Brand;
use Zenstruck\Foundry\Tests\Fixtures\Entity\Category;
use Zenstruck\Foundry\Tests\Fixtures\Entity\Comment;
use Zenstruck\Foundry\Tests\Fixtures\Entity\Contact;
use Zenstruck\Foundry\Tests\Fixtures\Entity\EntityWithRelations;
use Zenstruck\Foundry\Tests\Fixtures\Entity\Post as ORMPost;
use Zenstruck\Foundry\Tests\Fixtures\Entity\Tag;
use Zenstruck\Foundry\Tests\Fixtures\Factories\CategoryFactory;
use Zenstruck\Foundry\Tests\Fixtures\Factories\EntityForRelationsFactory;
use Zenstruck\Foundry\Tests\Fixtures\Factories\ODM\CommentFactory;
use Zenstruck\Foundry\Tests\Fixtures\Factories\ODM\UserFactory;
use Zenstruck\Foundry\Tests\Fixtures\Factories\PostFactory;
use Zenstruck\Foundry\Tests\Fixtures\Kernel;
use Zenstruck\Foundry\Tests\Fixtures\Object\SomeObject;
use Zenstruck\Foundry\Tests\Fixtures\PHP81\DocumentWithEnum;
use Zenstruck\Foundry\Tests\Fixtures\PHP81\EntityWithEnum;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 * @group maker
 * @requires PHP 8.1
 */
final class MakeFactoryTest extends MakerTestCase
{
    private const PHPSTAN_PATH = __DIR__.'/../../../..'.FactoryGenerator::PHPSTAN_PATH;
    private const PSALM_PATH = __DIR__.'/../../../..'.FactoryGenerator::PSALM_PATH;

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
    public function can_create_factory(): void
    {
        if (!\getenv('DATABASE_URL')) {
            self::markTestSkipped('doctrine/orm not enabled.');
        }

        $tester = $this->makeFactoryCommandTester([PostFactory::class]);

        $tester->execute(['class' => Category::class]);

        $output = $tester->getDisplay();

        $this->assertStringNotContainsString(ORMPost::class, $output);
        $this->assertStringContainsString('Note: pass --test if you want to generate factories in your tests/ directory', $output);

        $this->assertFileFromMakerSameAsExpectedFile(self::tempFile('src/Factory/CategoryFactory.php'));
    }

    /**
     * @test
     */
    public function can_create_factory_interactively(): void
    {
        if (!\getenv('DATABASE_URL')) {
            self::markTestSkipped('doctrine/orm not enabled.');
        }

        $tester = $this->makeFactoryCommandTester();

        $tester->setInputs([
            Comment::class, // which class to create a factory for?
            'no', // should create UserFactory for Comment::$user?
            'yes', // should create PostFactory for Comment::$post?
        ]);
        $tester->execute([], ['interactive' => true]);

        $output = $tester->getDisplay();
        $this->assertStringContainsString(
            'A factory for class "Zenstruck\Foundry\Tests\Fixtures\Entity\User" is missing for field Comment::$user. Do you want to create it?',
            $output,
        );

        $this->assertFileDoesNotExist(self::tempFile('src/Factory/UserFactory.php'));
        $this->assertFileExists(self::tempFile('src/Factory/PostFactory.php'));
        $this->assertFileFromMakerSameAsExpectedFile(self::tempFile('src/Factory/CommentFactory.php'));
    }

    /**
     * @test
     */
    public function can_create_factory_in_test_dir(): void
    {
        if (!\getenv('DATABASE_URL')) {
            self::markTestSkipped('doctrine/orm not enabled.');
        }

        $tester = $this->makeFactoryCommandTester();

        $tester->execute(['class' => Category::class, '--test' => true]);

        $this->assertFileFromMakerSameAsExpectedFile(self::tempFile('tests/Factory/CategoryFactory.php'));
    }

    /**
     * @test
     */
    public function can_create_factory_in_a_sub_directory(): void
    {
        if (!\getenv('DATABASE_URL')) {
            self::markTestSkipped('doctrine/orm not enabled.');
        }

        $tester = $this->makeFactoryCommandTester();

        $tester->execute(['class' => Brand::class]);
        $this->assertFileExists(self::tempFile('src/Factory/Cascade/BrandFactory.php'));

        $tester->execute(['class' => Brand::class, '--test' => true]);
        $this->assertFileExists(self::tempFile('tests/Factory/Cascade/BrandFactory.php'));
    }

    /**
     * @test
     */
    public function can_create_factory_in_test_dir_interactively(): void
    {
        if (!\getenv('DATABASE_URL')) {
            self::markTestSkipped('doctrine/orm not enabled.');
        }

        $tester = $this->makeFactoryCommandTester();

        $tester->setInputs([Tag::class]);
        $tester->execute(['--test' => true]);

        $this->assertFileFromMakerSameAsExpectedFile(self::tempFile('tests/Factory/TagFactory.php'));
    }

    /**
     * @test
     * @dataProvider scaToolProvider
     */
    public function can_create_factory_with_static_analysis_annotations(string $scaTool): void
    {
        if (!\getenv('DATABASE_URL')) {
            self::markTestSkipped('doctrine/orm not enabled.');
        }

        $this->emulateSCAToolEnabled($scaTool);

        $tester = $this->makeFactoryCommandTester();

        $tester->execute(['class' => Category::class]);

        $this->assertFileFromMakerSameAsExpectedFile(self::tempFile('src/Factory/CategoryFactory.php'));
    }

    /**
     * @test
     * @dataProvider scaToolProvider
     */
    public function can_create_factory_for_entity_with_repository(string $scaTool): void
    {
        if (!\getenv('DATABASE_URL')) {
            self::markTestSkipped('doctrine/orm not enabled.');
        }

        $this->emulateSCAToolEnabled($scaTool);

        $tester = $this->makeFactoryCommandTester();

        $tester->execute(['class' => ORMPost::class]);

        $this->assertFileFromMakerSameAsExpectedFile(self::tempFile('src/Factory/PostFactory.php'));
    }

    public function scaToolProvider(): iterable
    {
        yield 'phpstan' => [self::PHPSTAN_PATH];
        yield 'psalm' => [self::PSALM_PATH];
    }

    /**
     * @test
     */
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
    public function can_create_factory_for_not_persisted_class(): void
    {
        $tester = $this->makeFactoryCommandTester();

        $tester->execute(['class' => SomeObject::class, '--no-persistence' => true, '--all-fields' => true]);

        $this->assertFileFromMakerSameAsExpectedFile(self::tempFile('src/Factory/SomeObjectFactory.php'));
    }

    /**
     * @test
     */
    public function can_create_factory_for_not_persisted_class_interactively(): void
    {
        $tester = $this->makeFactoryCommandTester();

        $tester->setInputs(['Foo', SomeObject::class]); // "Foo" will generate a validation error
        $tester->execute(['--no-persistence' => true]);

        $output = $tester->getDisplay();

        $this->assertStringContainsString('Not persisted fully qualified class name to create a factory for:', $output);
        $this->assertStringContainsString('[ERROR] Given class "Foo" does not exist', $output);

        $this->assertFileFromMakerSameAsExpectedFile(self::tempFile('src/Factory/SomeObjectFactory.php'));
    }

    /**
     * @test
     */
    public function can_customize_namespace(): void
    {
        if (!\getenv('DATABASE_URL')) {
            self::markTestSkipped('doctrine/orm not enabled.');
        }

        $tester = $this->makeFactoryCommandTester();

        $tester->setInputs([Tag::class]);
        $tester->execute(['--namespace' => 'My\\Namespace']);

        $expectedFile = self::tempFile('src/My/Namespace/TagFactory.php');
        $this->assertFileExists($expectedFile);
        $this->assertStringContainsString('namespace App\\My\\Namespace;', \file_get_contents($expectedFile));
    }

    /**
     * @test
     */
    public function can_customize_namespace_with_test_flag(): void
    {
        if (!\getenv('DATABASE_URL')) {
            self::markTestSkipped('doctrine/orm not enabled.');
        }

        $tester = $this->makeFactoryCommandTester();

        $tester->setInputs([Tag::class]);
        $tester->execute(['--namespace' => 'My\\Namespace', '--test' => true]);

        $expectedFile = self::tempFile('tests/My/Namespace/TagFactory.php');
        $this->assertFileExists($expectedFile);
        $this->assertStringContainsString('namespace App\\Tests\\My\\Namespace;', \file_get_contents($expectedFile));
    }

    /**
     * @test
     */
    public function can_customize_namespace_with_root_namespace_prefix(): void
    {
        if (!\getenv('DATABASE_URL')) {
            self::markTestSkipped('doctrine/orm not enabled.');
        }

        $tester = $this->makeFactoryCommandTester();

        $tester->setInputs([Tag::class]);
        $tester->execute(['--namespace' => 'App\\My\\Namespace']);

        $expectedFile = self::tempFile('src/My/Namespace/TagFactory.php');
        $this->assertFileExists($expectedFile);
        $this->assertStringContainsString('namespace App\\My\\Namespace;', \file_get_contents($expectedFile));
    }

    /**
     * @test
     */
    public function can_customize_namespace_with_test_flag_with_root_namespace_prefix(): void
    {
        if (!\getenv('DATABASE_URL')) {
            self::markTestSkipped('doctrine/orm not enabled.');
        }

        $tester = $this->makeFactoryCommandTester();

        $tester->setInputs([Tag::class]);
        $tester->execute(['--namespace' => 'App\\Tests\\My\\Namespace', '--test' => true]);

        $expectedFile = self::tempFile('tests/My/Namespace/TagFactory.php');
        $this->assertFileExists($expectedFile);
        $this->assertStringContainsString('namespace App\\Tests\\My\\Namespace;', \file_get_contents($expectedFile));
    }

    /**
     * @test
     */
    public function can_create_all_factories_for_doctrine_objects(): void
    {
        $tester = $this->makeFactoryCommandTester();

        $inputs = ['All']; // which factory to generate?
        if (\getenv('DATABASE_URL')) {
            $inputs[] = 'OtherTagFactory'; // name collision handling (only for ORM, the collision is caused my an ORM class
        }

        $tester->setInputs($inputs);
        $tester->execute([]);

        $expectedFactories = [];

        if (\getenv('DATABASE_URL')) {
            $expectedFactories = ['Cascade/BrandFactory', 'CategoryFactory', 'CommentFactory', 'ContactFactory', 'EntityForRelationsFactory', 'UserFactory', 'TagFactory', 'Cascade/TagFactory'];
        }

        if (\getenv('MONGO_URL')) {
            $expectedFactories = [...$expectedFactories, 'ODMCategoryFactory', 'ODMCommentFactory', 'ODMPostFactory', 'ODMUserFactory'];
            $expectedFactories[] = \getenv('DATABASE_URL') ? 'OtherTagFactory' : 'TagFactory';
        }

        self::assertGreaterThan(0, \count($expectedFactories));
        foreach ($expectedFactories as $expectedFactory) {
            $this->assertFileExists(self::tempFile("src/Factory/{$expectedFactory}.php"));
        }
    }

    /**
     * @test
     * @dataProvider documentProvider
     */
    public function can_create_factory_for_odm(string $class, string $file): void
    {
        if (!\getenv('MONGO_URL')) {
            self::markTestSkipped('doctrine/odm not enabled.');
        }

        $tester = $this->makeFactoryCommandTester(['factoriesRegistered' => [UserFactory::class]]);

        $tester->setInputs([$class]);
        $tester->execute([]);

        $this->assertFileFromMakerSameAsExpectedFile(self::tempFile("src/Factory/{$file}.php"));
    }

    public function documentProvider(): iterable
    {
        yield 'document' => [ODMPost::class, 'ODMPostFactory'];
        yield 'embedded document' => [ODMComment::class, 'ODMCommentFactory'];
    }

    /**
     * @test
     */
    public function can_create_factory_with_auto_activated_not_persisted_option(): void
    {
        if (\getenv('USE_DAMA_DOCTRINE_TEST_BUNDLE')) {
            self::markTestSkipped('dama/doctrine-test-bundle should not be enabled.');
        }

        $tester = $this->makeFactoryCommandTester(['enableDoctrine' => false]);

        $tester->execute(['class' => Category::class]);

        $output = $tester->getDisplay();
        $this->assertStringContainsString('Note: Doctrine not enabled: auto-activating --no-persistence option.', $output);

        $this->assertFileFromMakerSameAsExpectedFile(self::tempFile('src/Factory/CategoryFactory.php'));
    }

    /**
     * @test
     */
    public function can_create_factory_with_relation_defaults(): void
    {
        if (!\getenv('DATABASE_URL')) {
            self::markTestSkipped('doctrine/orm not enabled.');
        }

        $tester = $this->makeFactoryCommandTester(['factoriesRegistered' => [CategoryFactory::class]]);

        $tester->execute(['class' => EntityWithRelations::class]);

        $this->assertFileFromMakerSameAsExpectedFile(self::tempFile('src/Factory/EntityWithRelationsFactory.php'));
    }

    /**
     * @test
     */
    public function can_create_factory_with_relation_for_all_fields(): void
    {
        if (!\getenv('DATABASE_URL')) {
            self::markTestSkipped('doctrine/orm not enabled.');
        }

        $tester = $this->makeFactoryCommandTester(['factoriesRegistered' => [CategoryFactory::class, EntityForRelationsFactory::class]]);

        $tester->execute(['class' => EntityWithRelations::class, '--all-fields' => true]);

        $this->assertFileFromMakerSameAsExpectedFile(self::tempFile('src/Factory/EntityWithRelationsFactory.php'));
    }

    /**
     * @test
     * @dataProvider objectsWithEmbeddableProvider
     */
    public function can_create_factory_with_embeddable(string $objectClass, string $objectFactoryName, string $embeddableObjectFactoryName, array $factoriesRegistered = []): void
    {
        $tester = $this->makeFactoryCommandTester(['factoriesRegistered' => $factoriesRegistered]);

        $tester->execute(['class' => $objectClass, '--all-fields' => true]);

        $this->assertFileExists(self::tempFile("src/Factory/{$embeddableObjectFactoryName}.php"));
        $this->assertFileFromMakerSameAsExpectedFile(self::tempFile("src/Factory/{$objectFactoryName}.php"));
    }

    public function objectsWithEmbeddableProvider(): iterable
    {
        if (\getenv('DATABASE_URL')) {
            yield 'orm' => [Contact::class, 'ContactFactory', 'AddressFactory'];
        }

        if (\getenv('MONGO_URL')) {
            yield 'odm' => [ODMPost::class, 'ODMPostFactory', 'ODMUserFactory', [CommentFactory::class]];
        }
    }

    /**
     * @test
     * @requires PHP 8.1
     * @dataProvider canCreateFactoryWithDefaultEnumProvider
     */
    public function can_create_factory_with_default_enum(string $class, bool $noPersistence = false): void
    {
        $tester = $this->makeFactoryCommandTester();

        $tester->execute(['class' => $class, '--no-persistence' => $noPersistence]);

        $factoryClass = Str::getShortClassName($class);
        $this->assertFileFromMakerSameAsExpectedFile(self::tempFile("src/Factory/{$factoryClass}Factory.php"));
    }

    public function canCreateFactoryWithDefaultEnumProvider(): iterable
    {
        if (\getenv('DATABASE_URL')) {
            yield 'orm' => [EntityWithEnum::class];
        }

        if (\getenv('MONGO_URL')) {
            yield 'odm' => [DocumentWithEnum::class];
        }

        yield 'without persistence' => [EntityWithEnum::class, true];
    }

    /**
     * @test
     */
    public function can_create_factory_for_orm_embedded_class(): void
    {
        if (!\getenv('DATABASE_URL')) {
            self::markTestSkipped('doctrine/odm not enabled.');
        }

        $tester = $this->makeFactoryCommandTester();

        $tester->execute(['class' => Address::class]);

        $factoryClass = Str::getShortClassName(Address::class);
        $this->assertFileFromMakerSameAsExpectedFile(self::tempFile("src/Factory/{$factoryClass}Factory.php"));
    }

    /**
     * @test
     */
    public function it_handles_name_collision(): void
    {
        if (!\getenv('DATABASE_URL') || !\getenv('MONGO_URL')) {
            self::markTestSkipped('doctrine/odm and doctrine/orm should be enabled enabled.');
        }

        $tester = $this->makeFactoryCommandTester();
        $tester->execute(['class' => Tag::class]);

        $tester->setInputs([
            'TagFactory', // first attempt to change the factory's name, will still break
            'OtherTagFactory', // second attempt, is OK
        ]);
        $tester->execute(['class' => AnotherTagClass::class]);

        $this->assertFileExists(self::tempFile('src/Factory/TagFactory.php'));
        $this->assertFileExists(self::tempFile('src/Factory/OtherTagFactory.php'));

        $output = $tester->getDisplay();

        $this->assertStringContainsString('Class "TagFactory" already exists', $output);
        $this->assertStringContainsString('[ERROR] Class "App\Factory\TagFactory" also already exists!', $output);
    }

    /**
     * @test
     * @dataProvider itUsesNamespaceFromConfigurationProvider
     */
    public function it_uses_default_namespace_from_configuration(string $defaultNamespace): void
    {
        if (!\getenv('DATABASE_URL')) {
            self::markTestSkipped('doctrine/orm not enabled.');
        }

        $tester = $this->makeFactoryCommandTester(['defaultMakeFactoryNamespace' => $defaultNamespace]);
        $tester->execute(['class' => Category::class]);

        $this->assertFileExists(self::tempFile('src/Foundry/CategoryFactory.php'));
    }

    public function itUsesNamespaceFromConfigurationProvider(): iterable
    {
        yield 'without root namespace' => ['Foundry'];
        yield 'with root namespace' => ['App\\Foundry'];
        yield 'with trailing backslash' => ['Foundry\\'];
    }

    protected static function createKernel(array $options = []): KernelInterface
    {
        return Kernel::create(
            enableDoctrine: $options['enableDoctrine'] ?? true,
            factoriesRegistered: $options['factoriesRegistered'] ?? [],
            defaultMakeFactoryNamespace: $options['defaultMakeFactoryNamespace'] ?? null,
        );
    }

    private function emulateSCAToolEnabled(string $scaToolFilePath): void
    {
        \mkdir(\dirname($scaToolFilePath), 0777, true);
        \touch($scaToolFilePath);
    }

    private function makeFactoryCommandTester(array $factoriesRegistered = []): CommandTester
    {
        return new CommandTester(
            (new Application(
                self::bootKernel($factoriesRegistered),
            ))->find('make:factory'),
        );
    }
}
