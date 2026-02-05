@withFixture(behat-contacts)
Feature: Test @withFixture tag

  Scenario: Load behat-contacts fixture with @withFixture tag
    Given there is a contact "jane-doe"
    Then 2 contacts should exist

  Scenario: Ensure DB is fresh
    Then 1 contact should exist
