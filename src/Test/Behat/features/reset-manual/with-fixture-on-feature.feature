@resetDB
@withFixture(behat-contacts)
Feature: "@withFixture" on feature

  Scenario: Ensure fixture is loaded
    Then 1 contact should exist

  Scenario: Ensure fixture is loaded once
    Then 1 contact should exist

  Scenario: Can add new data
    Given there is a contact
    Then 2 contacts should exist

  @resetDB
  Scenario: Reset DB should clear DB and reload fixture
    Then 1 contact should exist

