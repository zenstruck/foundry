<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Test\Behat\Tests\Fixture;

use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;
use Behat\Step\Then;
use Yceruto\BehatExtension\Context\ExceptionAssertionTrait;
use Zenstruck\Assert;

/**
 * Provides the exception assertion steps alongside the built-in FoundryContext.
 */
final class TestFoundryContext implements Context
{
    use ExceptionAssertionTrait;

    /**
     * The inline argument is resolved by the pattern-based transformation, the PyString by the
     * by-type one: comparing them proves multiline arguments get the same placeholder resolution.
     * Both sides stay equal when NO transformation runs at all, so also assert the placeholders
     * are really gone.
     */
    #[Then('/^each line of the following text should equal "(.*)":$/')]
    public function assertEachPyStringLineEquals(PyStringNode $pyString, string $expected): void
    {
        Assert::that($pyString->getStrings())->isNot([], 'The PyString is empty.');

        foreach ($pyString->getStrings() as $line) {
            Assert::that($line)->doesNotContain('<foundry:');
            Assert::that($line)->is($expected);
        }
    }

    #[Then('/^all cells of the following table should equal "(.*)":$/')]
    public function assertAllTableCellsEqual(TableNode $table, string $expected): void
    {
        foreach ($table->getRows() as $row) {
            Assert::that($row)->isNot([], 'The table row is empty.');

            foreach ($row as $cell) {
                Assert::that($cell)->doesNotContain('<foundry:');
                Assert::that($cell)->is($expected);
            }
        }
    }
}
