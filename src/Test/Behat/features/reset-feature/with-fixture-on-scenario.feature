Feature: "@withFixture" on scenario

  Scenario: Ensure DB is fresh
    Then 0 contacts should exist

  @withFixture(behat-contacts)
  Scenario: Ensure fixture is loaded
    Then 1 contact should exist

  Scenario: Ensure fixture is loaded once
    Then 1 contact should exist

  Scenario: Can add new data
    Given a contact is created
    Then 2 contacts should exist

  @resetDB
  Scenario: Reset DB should clear DB
    Then 0 contacts should exist

