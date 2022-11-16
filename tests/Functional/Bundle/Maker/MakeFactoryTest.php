<?php

namespace Zenstruck\Foundry\Tests\Functional\Bundle\Maker;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\MakerBundle\Exception\RuntimeCommandException;
use Symfony\Component\Console\Tester\CommandTester;
use Zenstruck\Foundry\Tests\Fixtures\Document\Comment;
use Zenstruck\Foundry\Tests\Fixtures\Document\Post;
use Zenstruck\Foundry\Tests\Fixtures\Entity\Category;
use Zenstruck\Foundry\Tests\Fixtures\Entity\Tag;
use Zenstruck\Foundry\Tests\Fixtures\Kernel;
use Zenstruck\Foundry\Tests\Fixtures\Object\SomeObject;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class MakeFactoryTest extends MakerTestCase
{
    private const PHPSTAN_PATH = __DIR__.'/../../../../vendor/phpstan/phpstan/phpstan';

    protected function tearDown(): void
    {
        parent::tearDown();

        if (\file_exists(self::PHPSTAN_PATH)) {
            \unlink(self::PHPSTAN_PATH);
            \rmdir(\dirname(self::PHPSTAN_PATH));
            \rmdir(\dirname(self::PHPSTAN_PATH, 2));
        }
    }

    /**
     * @test
     */
    public function can_create_factory(): void
    {
        if (!\getenv('USE_ORM')) {
            self::markTestSkipped('doctrine/orm not enabled.');
        }

        $tester = new CommandTester((new Application(self::bootKernel()))->find('make:factory'));

        $this->assertFileDoesNotExist(self::tempFile('src/Factory/CategoryFactory.php'));

        $tester->execute(['class' => Category::class]);

        $this->assertFileFromMakerSameAsExpectedFile(self::tempFile('src/Factory/CategoryFactory.php'));
    }

    /**
     * @test
     */
    public function can_create_factory_interactively(): void
    {
        if (!\getenv('USE_ORM')) {
            self::markTestSkipped('doctrine/orm not enabled.');
        }

        $tester = new CommandTester((new Application(self::bootKernel()))->find('make:factory'));

        $this->assertFileDoesNotExist(self::tempFile('src/Factory/TagFactory.php'));

        $tester->setInputs([Tag::class]);
        $tester->execute([]);

        $output = $tester->getDisplay();

        $this->assertStringNotContainsString(Category::class, $output);
        $this->assertStringContainsString('Note: pass --test if you want to generate factories in your tests/ directory', $output);

        $this->assertFileFromMakerSameAsExpectedFile(self::tempFile('src/Factory/TagFactory.php'));
    }

    /**
     * @test
     */
    public function can_create_factory_in_test_dir(): void
    {
        if (!\getenv('USE_ORM')) {
            self::markTestSkipped('doctrine/orm not enabled.');
        }

        $tester = new CommandTester((new Application(self::bootKernel()))->find('make:factory'));

        $this->assertFileDoesNotExist(self::tempFile('tests/Factory/CategoryFactory.php'));

        $tester->execute(['class' => Category::class, '--test' => true]);

        $this->assertFileFromMakerSameAsExpectedFile(self::tempFile('tests/Factory/CategoryFactory.php'));
    }

    /**
     * @test
     */
    public function can_create_factory_in_test_dir_interactively(): void
    {
        if (!\getenv('USE_ORM')) {
            self::markTestSkipped('doctrine/orm not enabled.');
        }

        $tester = new CommandTester((new Application(self::bootKernel()))->find('make:factory'));

        $this->assertFileDoesNotExist(self::tempFile('tests/Factory/TagFactory.php'));

        $tester->setInputs([Tag::class]);
        $tester->execute(['--test' => true]);

        $output = $tester->getDisplay();

        $this->assertStringNotContainsString(Category::class, $output);
        $this->assertStringNotContainsString('Note: pass --test if you want to generate factories in your tests/ directory', $output);

        $this->assertFileFromMakerSameAsExpectedFile(self::tempFile('tests/Factory/TagFactory.php'));
    }

    /**
     * @test
     */
    public function can_create_factory_with_phpstan_annotations(): void
    {
        if (!\getenv('USE_ORM')) {
            self::markTestSkipped('doctrine/orm not enabled.');
        }

        \mkdir(\dirname(self::PHPSTAN_PATH), 0777, true);
        \touch(self::PHPSTAN_PATH);

        $tester = new CommandTester((new Application(self::bootKernel()))->find('make:factory'));

        $this->assertFileDoesNotExist(self::tempFile('src/Factory/CategoryFactory.php'));

        $tester->execute(['class' => Category::class]);

        $this->assertFileFromMakerSameAsExpectedFile(self::tempFile('src/Factory/CategoryFactory.php'));
    }

    /**
     * @test
     */
    public function invalid_entity_throws_exception(): void
    {
        $tester = new CommandTester((new Application(self::bootKernel()))->find('make:factory'));

        $this->assertFileDoesNotExist(self::tempFile('src/Factory/InvalidFactory.php'));

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
        $tester = new CommandTester((new Application(self::bootKernel()))->find('make:factory'));

        $this->assertFileDoesNotExist(self::tempFile('src/Factory/SomeObjectFactory.php'));

        $tester->execute(['class' => SomeObject::class, '--not-persisted' => true, '--all-fields' => true]);

        $this->assertFileFromMakerSameAsExpectedFile(self::tempFile('src/Factory/SomeObjectFactory.php'));
    }

    /**
     * @test
     */
    public function can_create_factory_for_not_persisted_class_interactively(): void
    {
        $tester = new CommandTester((new Application(self::bootKernel()))->find('make:factory'));

        $this->assertFileDoesNotExist(self::tempFile('src/Factory/SomeObjectFactory.php'));

        $tester->setInputs(['Foo', SomeObject::class]); // "Foo" will generate a validation error
        $tester->execute(['--not-persisted' => true]);

        $output = $tester->getDisplay();

        $this->assertStringContainsString('Not persisted class to create a factory for:', $output);
        $this->assertStringContainsString('[ERROR] Given class "Foo" does not exist', $output);

        $this->assertFileFromMakerSameAsExpectedFile(self::tempFile('src/Factory/SomeObjectFactory.php'));
    }

    /**
     * @test
     */
    public function can_customize_namespace(): void
    {
        if (!\getenv('USE_ORM')) {
            self::markTestSkipped('doctrine/orm not enabled.');
        }

        $tester = new CommandTester((new Application(self::bootKernel()))->find('make:factory'));
        $expectedFile = self::tempFile('src/My/Namespace/TagFactory.php');

        $this->assertFileDoesNotExist($expectedFile);

        $tester->setInputs([Tag::class]);
        $tester->execute(['--namespace' => 'My\\Namespace']);

        $this->assertFileExists($expectedFile);
        $this->assertStringContainsString('namespace App\\My\\Namespace;', \file_get_contents($expectedFile));
    }

    /**
     * @test
     */
    public function can_customize_namespace_with_test_flag(): void
    {
        if (!\getenv('USE_ORM')) {
            self::markTestSkipped('doctrine/orm not enabled.');
        }

        $tester = new CommandTester((new Application(self::bootKernel()))->find('make:factory'));
        $expectedFile = self::tempFile('tests/My/Namespace/TagFactory.php');

        $this->assertFileDoesNotExist($expectedFile);

        $tester->setInputs([Tag::class]);
        $tester->execute(['--namespace' => 'My\\Namespace', '--test' => true]);

        $this->assertFileExists($expectedFile);
        $this->assertStringContainsString('namespace App\\Tests\\My\\Namespace;', \file_get_contents($expectedFile));
    }

    /**
     * @test
     */
    public function can_customize_namespace_with_root_namespace_prefix(): void
    {
        if (!\getenv('USE_ORM')) {
            self::markTestSkipped('doctrine/orm not enabled.');
        }

        $tester = new CommandTester((new Application(self::bootKernel()))->find('make:factory'));
        $expectedFile = self::tempFile('src/My/Namespace/TagFactory.php');

        $this->assertFileDoesNotExist($expectedFile);

        $tester->setInputs([Tag::class]);
        $tester->execute(['--namespace' => 'App\\My\\Namespace']);

        $this->assertFileExists($expectedFile);
        $this->assertStringContainsString('namespace App\\My\\Namespace;', \file_get_contents($expectedFile));
    }

    /**
     * @test
     */
    public function can_customize_namespace_with_test_flag_with_root_namespace_prefix(): void
    {
        if (!\getenv('USE_ORM')) {
            self::markTestSkipped('doctrine/orm not enabled.');
        }

        $tester = new CommandTester((new Application(self::bootKernel()))->find('make:factory'));
        $expectedFile = self::tempFile('tests/My/Namespace/TagFactory.php');

        $this->assertFileDoesNotExist($expectedFile);

        $tester->setInputs([Tag::class]);
        $tester->execute(['--namespace' => 'App\\Tests\\My\\Namespace', '--test' => true]);

        $this->assertFileExists($expectedFile);
        $this->assertStringContainsString('namespace App\\Tests\\My\\Namespace;', \file_get_contents($expectedFile));
    }

    /**
     * @test
     */
    public function can_create_factory_with_all_interactively(): void
    {
        $tester = new CommandTester((new Application(self::bootKernel()))->find('make:factory'));

        $this->assertFileDoesNotExist(self::tempFile('src/Factory/CategoryFactory.php'));
        $this->assertFileDoesNotExist(self::tempFile('src/Factory/PostFactory.php'));

        $tester->setInputs(['All']);

        try {
            $tester->execute([]);
        } catch (RuntimeCommandException) {
            // todo find a better solution
            // because we have fixtures with the same name, the maker will fail when creating the duplicate
        }

        $this->assertFileExists(self::tempFile('src/Factory/CategoryFactory.php'));
        $this->assertFileExists(self::tempFile('src/Factory/PostFactory.php'));
    }

    /**
     * @test
     * @dataProvider documentProvider
     */
    public function can_create_factory_for_odm(string $class, string $file): void
    {
        if (!\getenv('USE_ODM')) {
            self::markTestSkipped('doctrine/odm not enabled.');
        }

        $tester = new CommandTester((new Application(self::bootKernel()))->find('make:factory'));

        $this->assertFileDoesNotExist(self::tempFile("src/Factory/{$file}.php"));

        $tester->setInputs([$class]);
        $tester->execute([]);

        $this->assertFileFromMakerSameAsExpectedFile(self::tempFile("src/Factory/{$file}.php"));
    }

    public function documentProvider(): iterable
    {
        yield 'document' => [Post::class, 'PostFactory'];
        yield 'embedded document' => [Comment::class, 'CommentFactory'];
    }

    /**
     * @test
     */
    public function it_auto_activate_not_persisted_option_if_doctrine_not_enabled(): void
    {
        if (\getenv('USE_DAMA_DOCTRINE_TEST_BUNDLE')) {
            self::markTestSkipped('dama/doctrine-test-bundle should not be enabled.');
        }

        $kernel = Kernel::create(enableDoctrine: false);
        $kernel->boot();

        $tester = new CommandTester((new Application($kernel))->find('make:factory'));
        $this->assertFileDoesNotExist(self::tempFile('src/Factory/CategoryFactory.php'));

        $tester->execute(['class' => Category::class]);

        $output = $tester->getDisplay();
        $this->assertStringContainsString('Note: Doctrine not enabled: auto-activating --not-persisted option.', $output);

        $this->assertFileFromMakerSameAsExpectedFile(self::tempFile('src/Factory/CategoryFactory.php'));
    }
}
