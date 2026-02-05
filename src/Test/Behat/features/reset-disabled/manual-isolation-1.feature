Feature: No database isolation (disabled mode) - Part 1

  Scenario: First scenario creates data
    Given there is a contact A
    Then 1 contact should exist

  Scenario: Second scenario sees previous data (no reset)
    Then 1 contact should exist
    Then contact A should exist
    Given there is a contact B
    Then 2 contacts should exist

  Scenario: Third scenario sees all accumulated data
    Then 2 contacts should exist
    Then contact A should exist
    Then contact B should exist
