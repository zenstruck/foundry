Feature: Manual database isolation (disabled mode) - Part 2

  Scenario: Contacts still exist from previous feature
    Then 2 contacts should exist

    # ObjectRegistry is reset between features even when isolation is disabled
    Then contact A should not exist

  @resetDB
  Scenario: DB should be reset
    Then 0 contacts should exist

  Scenario: Create another contact
    Given there is a contact
    Then 1 contact should exist
