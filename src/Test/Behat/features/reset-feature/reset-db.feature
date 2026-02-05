Feature: Manual database reset with @resetDB tag

  Scenario: Ensure fresh DB
    Then 0 contact should exist

  Scenario: Create one contact
    Given there is a contact A
    Then 1 contact should exist

  Scenario: Ensure contact still exists
    Then 1 contact should exist
    Then contact A should exist

  @resetDB
  Scenario: Database is reset with @resetDB tag
    Then 0 contacts should exist
    Then contact A should not exist
    Given there is a contact
    Then 1 contact should exist

  Scenario: Data from tagged scenario persists
    Then 1 contact should exist
