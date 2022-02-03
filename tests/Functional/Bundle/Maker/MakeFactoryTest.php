<?php

namespace Zenstruck\Foundry\Tests\Functional\Bundle\Maker;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\MakerBundle\Exception\RuntimeCommandException;
use Symfony\Component\Console\Tester\CommandTester;
use Zenstruck\Foundry\Tests\Fixtures\Document\Comment;
use Zenstruck\Foundry\Tests\Fixtures\Document\Post;
use Zenstruck\Foundry\Tests\Fixtures\Entity\Category;
use Zenstruck\Foundry\Tests\Fixtures\Entity\Tag;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class MakeFactoryTest extends MakerTestCase
{
    /**
     * @test
     */
    public function can_create_factory(): void
    {
        $tester = new CommandTester((new Application(self::bootKernel()))->find('make:factory'));

        $this->assertFileDoesNotExist(self::tempFile('src/Factory/CategoryFactory.php'));

        $tester->execute(['entity' => Category::class]);

        $this->assertFileExists(self::tempFile('src/Factory/CategoryFactory.php'));
        $this->assertSame(<<<EOF
<?php

namespace App\\Factory;

use Zenstruck\\Foundry\\Tests\\Fixtures\\Entity\\Category;
use Zenstruck\\Foundry\\ModelFactory;
use Zenstruck\\Foundry\\Proxy;

/**
 * @extends ModelFactory<Category>
 *
 * @method static Category|Proxy createOne(array \$attributes = [])
 * @method static Category[]|Proxy[] createMany(int \$number, array|callable \$attributes = [])
 * @method static Category|Proxy find(object|array|mixed \$criteria)
 * @method static Category|Proxy findOrCreate(array \$attributes)
 * @method static Category|Proxy first(string \$sortedField = 'id')
 * @method static Category|Proxy last(string \$sortedField = 'id')
 * @method static Category|Proxy random(array \$attributes = [])
 * @method static Category|Proxy randomOrCreate(array \$attributes = [])
 * @method static Category[]|Proxy[] all()
 * @method static Category[]|Proxy[] findBy(array \$attributes)
 * @method static Category[]|Proxy[] randomSet(int \$number, array \$attributes = [])
 * @method static Category[]|Proxy[] randomRange(int \$min, int \$max, array \$attributes = [])
 * @method Category|Proxy create(array|callable \$attributes = [])
 */
final class CategoryFactory extends ModelFactory
{
    public function __construct()
    {
        parent::__construct();

        // TODO inject services if required (https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#factories-as-services)
    }

    protected function getDefaults(): array
    {
        return [
            // TODO add your default values here (https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#model-factories)
            'name' => self::faker()->text(),
        ];
    }

    protected function initialize(): self
    {
        // see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
        return \$this
            // ->afterInstantiate(function(Category \$category): void {})
        ;
    }

    protected static function getClass(): string
    {
        return Category::class;
    }
}

EOF
            , \file_get_contents(self::tempFile('src/Factory/CategoryFactory.php'))
        );
    }

    /**
     * @test
     */
    public function can_create_factory_interactively(): void
    {
        $tester = new CommandTester((new Application(self::bootKernel()))->find('make:factory'));

        $this->assertFileDoesNotExist(self::tempFile('src/Factory/TagFactory.php'));

        $tester->setInputs([Tag::class]);
        $tester->execute([]);
        $output = $tester->getDisplay();

        $this->assertStringNotContainsString(Category::class, $output);
        $this->assertFileExists(self::tempFile('src/Factory/TagFactory.php'));
        $this->assertStringContainsString('Note: pass --test if you want to generate factories in your tests/ directory', $output);
        $this->assertSame(<<<EOF
<?php

namespace App\\Factory;

use Zenstruck\\Foundry\\Tests\\Fixtures\\Entity\\Tag;
use Zenstruck\\Foundry\\ModelFactory;
use Zenstruck\\Foundry\\Proxy;

/**
 * @extends ModelFactory<Tag>
 *
 * @method static Tag|Proxy createOne(array \$attributes = [])
 * @method static Tag[]|Proxy[] createMany(int \$number, array|callable \$attributes = [])
 * @method static Tag|Proxy find(object|array|mixed \$criteria)
 * @method static Tag|Proxy findOrCreate(array \$attributes)
 * @method static Tag|Proxy first(string \$sortedField = 'id')
 * @method static Tag|Proxy last(string \$sortedField = 'id')
 * @method static Tag|Proxy random(array \$attributes = [])
 * @method static Tag|Proxy randomOrCreate(array \$attributes = [])
 * @method static Tag[]|Proxy[] all()
 * @method static Tag[]|Proxy[] findBy(array \$attributes)
 * @method static Tag[]|Proxy[] randomSet(int \$number, array \$attributes = [])
 * @method static Tag[]|Proxy[] randomRange(int \$min, int \$max, array \$attributes = [])
 * @method Tag|Proxy create(array|callable \$attributes = [])
 */
final class TagFactory extends ModelFactory
{
    public function __construct()
    {
        parent::__construct();

        // TODO inject services if required (https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#factories-as-services)
    }

    protected function getDefaults(): array
    {
        return [
            // TODO add your default values here (https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#model-factories)
            'name' => self::faker()->text(),
        ];
    }

    protected function initialize(): self
    {
        // see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
        return \$this
            // ->afterInstantiate(function(Tag \$tag): void {})
        ;
    }

    protected static function getClass(): string
    {
        return Tag::class;
    }
}

EOF
            , \file_get_contents(self::tempFile('src/Factory/TagFactory.php'))
        );
    }

    /**
     * @test
     */
    public function can_create_factory_in_test_dir(): void
    {
        $tester = new CommandTester((new Application(self::bootKernel()))->find('make:factory'));

        $this->assertFileDoesNotExist(self::tempFile('tests/Factory/CategoryFactory.php'));

        $tester->execute(['entity' => Category::class, '--test' => true]);

        $this->assertFileExists(self::tempFile('tests/Factory/CategoryFactory.php'));
        $this->assertSame(<<<EOF
<?php

namespace App\\Tests\\Factory;

use Zenstruck\\Foundry\\Tests\\Fixtures\\Entity\\Category;
use Zenstruck\\Foundry\\ModelFactory;
use Zenstruck\\Foundry\\Proxy;

/**
 * @extends ModelFactory<Category>
 *
 * @method static Category|Proxy createOne(array \$attributes = [])
 * @method static Category[]|Proxy[] createMany(int \$number, array|callable \$attributes = [])
 * @method static Category|Proxy find(object|array|mixed \$criteria)
 * @method static Category|Proxy findOrCreate(array \$attributes)
 * @method static Category|Proxy first(string \$sortedField = 'id')
 * @method static Category|Proxy last(string \$sortedField = 'id')
 * @method static Category|Proxy random(array \$attributes = [])
 * @method static Category|Proxy randomOrCreate(array \$attributes = [])
 * @method static Category[]|Proxy[] all()
 * @method static Category[]|Proxy[] findBy(array \$attributes)
 * @method static Category[]|Proxy[] randomSet(int \$number, array \$attributes = [])
 * @method static Category[]|Proxy[] randomRange(int \$min, int \$max, array \$attributes = [])
 * @method Category|Proxy create(array|callable \$attributes = [])
 */
final class CategoryFactory extends ModelFactory
{
    public function __construct()
    {
        parent::__construct();

        // TODO inject services if required (https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#factories-as-services)
    }

    protected function getDefaults(): array
    {
        return [
            // TODO add your default values here (https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#model-factories)
            'name' => self::faker()->text(),
        ];
    }

    protected function initialize(): self
    {
        // see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
        return \$this
            // ->afterInstantiate(function(Category \$category): void {})
        ;
    }

    protected static function getClass(): string
    {
        return Category::class;
    }
}

EOF
            , \file_get_contents(self::tempFile('tests/Factory/CategoryFactory.php'))
        );
    }

    /**
     * @test
     */
    public function can_create_factory_in_test_dir_interactively(): void
    {
        $tester = new CommandTester((new Application(self::bootKernel()))->find('make:factory'));

        $this->assertFileDoesNotExist(self::tempFile('tests/Factory/TagFactory.php'));

        $tester->setInputs([Tag::class]);
        $tester->execute(['--test' => true]);
        $output = $tester->getDisplay();

        $this->assertFileExists(self::tempFile('tests/Factory/TagFactory.php'));
        $this->assertStringNotContainsString(Category::class, $output);
        $this->assertStringNotContainsString('Note: pass --test if you want to generate factories in your tests/ directory', $output);
        $this->assertSame(<<<EOF
<?php

namespace App\\Tests\\Factory;

use Zenstruck\\Foundry\\Tests\\Fixtures\\Entity\\Tag;
use Zenstruck\\Foundry\\ModelFactory;
use Zenstruck\\Foundry\\Proxy;

/**
 * @extends ModelFactory<Tag>
 *
 * @method static Tag|Proxy createOne(array \$attributes = [])
 * @method static Tag[]|Proxy[] createMany(int \$number, array|callable \$attributes = [])
 * @method static Tag|Proxy find(object|array|mixed \$criteria)
 * @method static Tag|Proxy findOrCreate(array \$attributes)
 * @method static Tag|Proxy first(string \$sortedField = 'id')
 * @method static Tag|Proxy last(string \$sortedField = 'id')
 * @method static Tag|Proxy random(array \$attributes = [])
 * @method static Tag|Proxy randomOrCreate(array \$attributes = [])
 * @method static Tag[]|Proxy[] all()
 * @method static Tag[]|Proxy[] findBy(array \$attributes)
 * @method static Tag[]|Proxy[] randomSet(int \$number, array \$attributes = [])
 * @method static Tag[]|Proxy[] randomRange(int \$min, int \$max, array \$attributes = [])
 * @method Tag|Proxy create(array|callable \$attributes = [])
 */
final class TagFactory extends ModelFactory
{
    public function __construct()
    {
        parent::__construct();

        // TODO inject services if required (https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#factories-as-services)
    }

    protected function getDefaults(): array
    {
        return [
            // TODO add your default values here (https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#model-factories)
            'name' => self::faker()->text(),
        ];
    }

    protected function initialize(): self
    {
        // see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
        return \$this
            // ->afterInstantiate(function(Tag \$tag): void {})
        ;
    }

    protected static function getClass(): string
    {
        return Tag::class;
    }
}

EOF
            , \file_get_contents(self::tempFile('tests/Factory/TagFactory.php'))
        );
    }

    /**
     * @test
     */
    public function invalid_entity_throws_exception(): void
    {
        $tester = new CommandTester((new Application(self::bootKernel()))->find('make:factory'));

        $this->assertFileDoesNotExist(self::tempFile('src/Factory/InvalidFactory.php'));

        try {
            $tester->execute(['entity' => 'Invalid']);
        } catch (RuntimeCommandException $e) {
            $this->assertSame('Entity "Invalid" not found.', $e->getMessage());
            $this->assertFileDoesNotExist(self::tempFile('src/Factory/InvalidFactory.php'));

            return;
        }

        $this->fail('Exception not thrown.');
    }

    /**
     * @test
     */
    public function can_customize_namespace(): void
    {
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
        } catch (RuntimeCommandException $e) {
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
        if (false === \getenv('MONGO_URL')) {
            self::markTestSkipped('doctrine/odm not enabled.');
        }

        $tester = new CommandTester((new Application(self::bootKernel()))->find('make:factory'));

        $this->assertFileDoesNotExist(self::tempFile("src/Factory/{$file}.php"));

        $tester->setInputs([$class]);
        $tester->execute([]);

        $this->assertFileExists(self::tempFile("src/Factory/{$file}.php"));
    }

    public function documentProvider(): iterable
    {
        yield 'document' => [Post::class, 'PostFactory'];
        yield 'embedded document' => [Comment::class, 'CommentFactory'];
    }
}
