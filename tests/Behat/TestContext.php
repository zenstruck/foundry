<?php

namespace Zenstruck\Foundry\Tests\Behat;

use Behat\Behat\Context\Context;
use Zenstruck\Foundry\Tests\Fixtures\Factories\CategoryFactory;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class TestContext implements Context
{
    /**
     * @Given /^(?P<count>\d+) (?:category has|categories have) been created$/
     * @When /^I create (?P<count>\d+) categor(?:y|ies)$/
     */
    public function createCategories(int $count): void
    {
        CategoryFactory::createMany($count);
    }

    /**
     * @Then /^there should be (?P<count>\d+) categor(?:y|ies) in the database$/
     */
    public function assertCategoryCount(int $count): void
    {
        CategoryFactory::assert()->count($count);
    }
}
