<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Test\Behat\Tests\Integration;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

/**
 * Proves that a context composed from the step traits really replaces the built-in
 * wordings of the methods it re-defines: the old wordings are not matched anymore,
 * while the new ones are (see OverridingFoundryContext).
 */
final class OverriddenStepsReplacementTest extends TestCase
{
    #[Test]
    public function overriding_wordings_are_defined(): void
    {
        [$exitCode, $output] = self::runBehat(['--profile=override-steps', '--dry-run', '--lang=en']);

        self::assertSame(0, $exitCode, $output);
        self::assertStringNotContainsString('undefined', $output);
    }

    #[Test]
    public function definition_list_contains_the_new_wordings_but_not_the_builtin_ones(): void
    {
        [$exitCode, $output] = self::runBehat(['--profile=override-steps', '-di']);

        self::assertSame(0, $exitCode, $output);

        self::assertStringContainsString('create a :factoryShortName called :objectName', $output);
        self::assertStringContainsString('an? (\S+) exists with:', $output);
        self::assertStringContainsString('the following :factoryShortName exist:', $output);

        self::assertStringNotContainsString('there is a(n) :factoryShortName', $output);
        self::assertStringNotContainsString('there are :factoryShortName with:', $output);
    }

    /**
     * @param list<string> $args
     *
     * @return array{0: int, 1: string}
     */
    private static function runBehat(array $args): array
    {
        $behatDir = \dirname(__DIR__, 2);
        $process = new Process([\PHP_BINARY, "{$behatDir}/vendor/bin/behat", '-c', "{$behatDir}/behat.php", ...$args]);
        $process->run();

        return [(int) $process->getExitCode(), $process->getOutput().$process->getErrorOutput()];
    }
}
