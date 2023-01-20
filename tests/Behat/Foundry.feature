Feature: Behat support for Foundry

    As a developer writing tests with Behat, I want to be able to
    use Foundry for creating test data. Foundry needs to be configured,
    started and stopped accordingly. The database has to be reset before
    scenarios are run.

    Scenario: Creating two categories
        When I create 2 categories
        Then there should be 2 categories in the database

    Scenario: Adding to the database
        Given 1 category has been created
        When I create 2 categories
        Then there should be 3 categories in the database
