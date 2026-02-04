Feature: Manual database reset with @resetDB tag

  Scenario: Ensure fresh DB
    Then 0 contact should exist

  Scenario: Create one contact
    Given a contact A is created
    Then 1 contact should exist

  Scenario: Ensure contact still exists
    Then 1 contact should exist
    Then contact object named A should exist

  @resetDB
  Scenario: Database is reset with @resetDB tag
    Then 0 contacts should exist
    Then contact object named A should not exist
    Given a contact is created
    Then 1 contact should exist

  Scenario: Data from tagged scenario persists
    Then 1 contact should exist
