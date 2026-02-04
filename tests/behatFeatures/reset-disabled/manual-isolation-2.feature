# resetDB should have no effect
@resetDB
Feature: No database isolation (disabled mode) - Part 2

  Scenario: Contacts still exist from previous feature
    Then 2 contacts should exist

    # ObjectRegistry is reset between features even when isolation is disabled
    Then contact object named A should not exist

  @resetDB
  Scenario: DB should be reset
    Then 2 contacts should exist
