<?php

namespace Zenstruck\Foundry\Tests\Functional\Bundle\Maker;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class MakeStoryTest extends MakerTestCase
{
    /**
     * @test
     * @dataProvider storyNameProvider
     */
    public function can_create_story($name): void
    {
        $tester = new CommandTester((new Application(self::bootKernel()))->find('make:story'));

        $this->assertFileDoesNotExist(self::tempFile('src/Story/FooBarStory.php'));

        $tester->execute(['name' => $name]);

        $this->assertFileExists(self::tempFile('src/Story/FooBarStory.php'));
        $this->assertSame(<<<EOF
<?php

namespace App\\Story;

use Zenstruck\\Foundry\\Story;

final class FooBarStory extends Story
{
    public function build(): void
    {
        // TODO build your story here (https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#stories)
    }
}

EOF
            , \file_get_contents(self::tempFile('src/Story/FooBarStory.php'))
        );
    }

    /**
     * @test
     * @dataProvider storyNameProvider
     */
    public function can_create_story_interactively($name): void
    {
        $tester = new CommandTester((new Application(self::bootKernel()))->find('make:story'));

        $this->assertFileDoesNotExist(self::tempFile('src/Story/FooBarStory.php'));

        $tester->setInputs([$name]);
        $tester->execute([]);
        $output = $tester->getDisplay();

        $this->assertFileExists(self::tempFile('src/Story/FooBarStory.php'));
        $this->assertStringContainsString('Note: pass --test if you want to generate stories in your tests/ directory', $output);
        $this->assertSame(<<<EOF
<?php

namespace App\\Story;

use Zenstruck\\Foundry\\Story;

final class FooBarStory extends Story
{
    public function build(): void
    {
        // TODO build your story here (https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#stories)
    }
}

EOF
            , \file_get_contents(self::tempFile('src/Story/FooBarStory.php'))
        );
    }

    /**
     * @test
     * @dataProvider storyNameProvider
     */
    public function can_create_story_in_test_dir($name): void
    {
        $tester = new CommandTester((new Application(self::bootKernel()))->find('make:story'));

        $this->assertFileDoesNotExist(self::tempFile('tests/Story/FooBarStory.php'));

        $tester->execute(['name' => $name, '--test' => true]);

        $this->assertFileExists(self::tempFile('tests/Story/FooBarStory.php'));
        $this->assertSame(<<<EOF
<?php

namespace App\\Tests\\Story;

use Zenstruck\\Foundry\\Story;

final class FooBarStory extends Story
{
    public function build(): void
    {
        // TODO build your story here (https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#stories)
    }
}

EOF
            , \file_get_contents(self::tempFile('tests/Story/FooBarStory.php'))
        );
    }

    /**
     * @test
     * @dataProvider storyNameProvider
     */
    public function can_create_story_in_test_dir_interactively($name): void
    {
        $tester = new CommandTester((new Application(self::bootKernel()))->find('make:story'));

        $this->assertFileDoesNotExist(self::tempFile('tests/Story/FooBarStory.php'));

        $tester->setInputs([$name]);
        $tester->execute(['--test' => true]);
        $output = $tester->getDisplay();

        $this->assertFileExists(self::tempFile('tests/Story/FooBarStory.php'));
        $this->assertStringNotContainsString('Note: pass --test if you want to generate stories in your tests/ directory', $output);
        $this->assertSame(<<<EOF
<?php

namespace App\\Tests\\Story;

use Zenstruck\\Foundry\\Story;

final class FooBarStory extends Story
{
    public function build(): void
    {
        // TODO build your story here (https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#stories)
    }
}

EOF
            , \file_get_contents(self::tempFile('tests/Story/FooBarStory.php'))
        );
    }

    public static function storyNameProvider(): iterable
    {
        yield ['FooBar'];
        yield ['FooBarStory'];
    }
}
