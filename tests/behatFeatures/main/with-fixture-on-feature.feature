@withFixture(behat-contacts)
Feature: Test @withFixture tag

  Scenario: Load behat-contacts fixture with @withFixture tag
    Given a contact "jane-doe" is created
    Then 2 contacts should exist

  Scenario: Ensure DB is fresh
    Then 1 contact should exist
