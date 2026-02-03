@resetDB
Feature: Manual database isolation (disabled mode) - Part 1

  Scenario: First scenario creates data
    Given a contact A is created
    Then 1 contact should exist

  Scenario: Second scenario sees previous data (no reset)
    Then 1 contact should exist
    Then contact object named A should exist
    Given a contact B is created
    Then 2 contacts should exist

  Scenario: Third scenario sees all accumulated data
    Then 2 contacts should exist
    Then contact object named A should exist
    Then contact object named B should exist
