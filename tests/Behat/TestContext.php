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
     * @Given there is :count category
     */
    public function thereIsCategory(int $count): void
    {
        CategoryFactory::createMany($count);
    }

    /**
     * @Then there is :count category in the database
     */
    public function thereIsCategoryInTheDatabase(int $count): void
    {
        CategoryFactory::assert()->count($count);
    }
}
